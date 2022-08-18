/*
 *  MPEG TS packet checker 
 */


#include "def.h"

#define STAT_SIZE 8192

int packetchk(const char *path)
{
  int   fd;
  int   pid;
  int   idx = 0;
  int   lcc;
  int   i,m,n;
  int   unit_size;
  INT64  total;
  INT64  startPCR = 0;
  INT64  currPCR = 0;
  int    syncbyte_lost = 0;
  int    pcc = 1;                  /* packet counter */
  
  TS_STATUS         *stat;
  TS_HEADER         header;
  ADAPTATION_FIELD  adapt;
  VERBOSE_DATA      vd = { 1,false };
  
  unsigned char *p;
  unsigned char buf[BUF_SIZE];
  char          text[128];
  struct timespec startT,endT;

  fd = -1;
  stat = NULL;
  clock_gettime(CLOCK_REALTIME, &startT);
  
  fd = open(path, O_RDONLY);
  if(fd < 0){
    if ( opt_S == false )
    	fprintf(stderr, "Error: file not open(%s)\n",strerror(errno));
    return 1;
  }

  total = lseek64(fd, 0, SEEK_END);
  //total = tell64(fd);
  lseek64(fd, 0, SEEK_SET);
  
  stat = (TS_STATUS *)calloc(STAT_SIZE , sizeof(TS_STATUS));
  if(stat == NULL){
    if ( opt_S == false )
      fprintf(stderr, "Error: failed on malloc(size=%ld)\n", sizeof(TS_STATUS)*STAT_SIZE);
    return 1;
  }

  for(i=0;i< STAT_SIZE;i++){
    stat[i].pid = i;
    stat[i].last_continuity_counter = -1;
  }

  ReadBuf rb = { fd, buf,sizeof(buf),0,0,0,false,0 };
  n = read_buf( &rb );

  unit_size = select_unit_size(buf, buf+n);
  if(unit_size < 188){
    if ( opt_S == false )
      fprintf(stderr, "Error: syncbyte not found(unit_size<188)\n");
    return 1;
  }
  rb.unit_size = unit_size ;

  p = resync( rb.curr, rb.tail, unit_size);
  if ( p == NULL ) {
    if ( opt_S == false )
      fprintf(stderr, "Error: syncbyte not found(p=NULL)\n");
    return 1;
  } else {
    rb.curr = p ;
  }

  while ( packetEmpty( &rb ) == false ) {

    /* 同期byte のチェック */
    if ( *rb.curr != 0x47 ) {
      if (verbose(&vd,currPCR-startPCR , pcc, "syncbyte lost", 0 )== false ) {
        syncbyte_lost++;
      }
      p = resyncRB( &rb );
      if ( p == NULL ) break;
      rb.curr = p;
    }

    /* ヘッダー解析 */
    extract_ts_header(&header, rb.curr);
    if(header.adaptation_field_control & 2){
      extract_adaptation_field(&adapt, rb.curr+4);
    }else{
      memset(&adapt, 0, sizeof(adapt));
    }

    INT64  tmp = getPCR(&header, &adapt );
    if ( tmp != 0 ) {
      currPCR = tmp;
      if ( startPCR == 0 )  startPCR = currPCR ;
    }
  
    pid = header.pid;
    lcc = stat[pid].last_continuity_counter;

    if ( header.transport_error_indicator != 0) { // 伝送エラー
      if ( verbose( &vd, currPCR - startPCR, pcc, "error", pid) == false ) {
        stat[pid].error += 1;
      }
    } else if( (lcc >= 0) && (adapt.discontinuity_counter == 0) ){
      if( pid == 0x1fff ){
        // null packet - drop count has no mean
        // do nothing
      } else if( (header.adaptation_field_control & 0x01) == 0 ){
        // no payload : continuity_counter should not increment
        if(lcc != header.continuity_counter  ){
          sprintf( text, "drop (no payload: %d != %d)", lcc, header.continuity_counter );
          if ( verbose( &vd, currPCR - startPCR , pcc, text, pid) == false ) {
            stat[pid].drop += 1;
          }
        }
      } else if(lcc == header.continuity_counter ) { // 再送の可能性
        // has payload and same continuity_counter
        if(memcmp(stat[pid].last_packet, rb.curr, 188) != 0){ // 中身が違う
          // non-duplicate packet
          sprintf( text, "drop (%d != %d)", lcc+1, header.continuity_counter );
          if ( verbose( &vd, currPCR - startPCR , pcc, text, pid) == false ) {
            stat[pid].drop += 1;
          }
        } else {                // 中身が同じ -> 再送は 1回まで
          stat[pid].duplicate_count += 1;
          if(stat[pid].duplicate_count > 1){
            // duplicate packet count exceeds limit (two)
            sprintf( text, "drop (Abnormal duplicate count)");
            if ( verbose( &vd, currPCR - startPCR , pcc, text, pid) == false ) {
              stat[pid].drop += 1;
            }
          }
        }
      } else {
        // 通常時
        m = (lcc + 1) & 0x0f;
        if(m != header.continuity_counter){
          sprintf( text, "drop (%d != %d)", m,header.continuity_counter );
          if ( verbose( &vd, currPCR - startPCR, pcc, text, pid) == false ) {
            stat[pid].drop += 1;
          }
        }
        stat[pid].duplicate_count = 0;
      }
    }
    if(header.transport_scrambling_control) {
      stat[pid].scrambling += 1;
    }
    stat[pid].last_continuity_counter = header.continuity_counter;

    memcpy(stat[pid].last_packet, rb.curr, 188);
    stat[pid].total += 1;
    rb.curr += unit_size;
    pcc += 1; 

    if( opt_p == true ) {
      if (idx > ( 1024 * 128 )) {
        n = (int)(1000 * rb.total / total);
        fprintf(stderr, "\rprocessing: %2d.%01d%%", n/10, n%10);
        idx = 0;
      }
      idx += 1;
    }
  } 

  if ( opt_p == true ) {
    fprintf(stderr, "\rprocessing: finish\n");
  }

LAST:
  if(stat){
    char sep[] = "-----------------------------------------------------------";
    puts("");
    printf("%6s %12s %12s %12s %12s\n","pid","packets","drop","error","scrambling");
    puts(sep);

    long packetNo = 0;
    long drop = 0;
    long error = 0;
    long scram = 0;
    for(i=0;i<STAT_SIZE;i++){
      if(stat[i].total > 0){
        printf("0x%04x %12ld %12ld %12ld %12ld\n",
               i, stat[i].total, stat[i].drop, stat[i].error, stat[i].scrambling);
        packetNo += stat[i].total; 
        drop     += stat[i].drop;
        error    += stat[i].error;
        scram    += stat[i].scrambling;
      }
    }
    puts(sep);
    printf("       %12ld %12ld %12ld %12ld\n", packetNo, drop, error, scram );
    printf("\n");
    if ( opt_S == true ) {
      fprintf(stderr, "drop+err=%ld\n", drop + error);
      fprintf(stderr, "sb lost=%d\n", syncbyte_lost );
    } else {	
      printf("%22s = %ld\n","drop+error", drop + error);
      printf("%22s = %d\n", "syncbyte lost",syncbyte_lost );
    }

    free(stat);
    stat = NULL;
  }

  if ( opt_P == true ) {
    if ( startPCR != 0 && currPCR != 0 ) {
      char tmpS[16],tmpE[16];
      strcpy(tmpS, pcr2str( startPCR ));
      strcpy(tmpE, pcr2str( currPCR ));
      char *result = startPCR < currPCR ? "OK" : "NG" ;
      if ( opt_S == true ) {
        fprintf(stderr, "PCR check=%s(S=%s,E=%s)\n", result, tmpS, tmpE );
      } else {
        printf("%22s = %s          (start=%s, end=%s)\n",
               "PCR Wrap-around check",result, tmpS,tmpE );
      }
    }
  }
  if ( opt_S == true ) {
    double Gbyte = (double) rb.total / (1024 * 1024 * 1024);
    fprintf(stderr, "time=%s\n", pcr2str(durationCalc( startPCR, currPCR )));
    fprintf(stderr, "size=%.2fGB", Gbyte );
  } else {
    printf("%22s = %s (%d packets, %lld byte)\n","duration",
         pcr2str(durationCalc( startPCR, currPCR )),pcc-1, rb.total );
  }

  if ( opt_S == true ) { //出力なし
  } else {
    clock_gettime(CLOCK_REALTIME, &endT);
    int sec = endT.tv_sec - startT.tv_sec;
    int nsec = endT.tv_nsec - startT.tv_nsec;
    double d_sec = (double)sec + (double)nsec / (1000 * 1000 * 1000);
    double speed = rb.total ;
    speed = speed / d_sec / 1000000 ;
    sprintf( text, "%.1f sec",d_sec );
    printf("%22s = %-10s  (%.2f Mbyte/sec)\n","Check Time",
           text, speed );
  }
  fflush(stdout);

  if(fd >= 0){
    close(fd);
    fd = -1;
  }
  return 0;
}

/*
 * duration の計算 (PCR Wrap-around を考慮)
 */
INT64 durationCalc( INT64 start, INT64 end )
{
  if ( end > start ) {
    return ( end - start );
  } else {
    return ( 0x1ffffffff - start ) + end ;
  }
}


/*
 *  詳細表示
 */ 
bool verbose( VERBOSE_DATA *vd, INT64  pcr, int pcc, char *type, int pid)
{

  if ( skip( pcr ) == true ) return true ;
  
  if ( vd->lineC == 1 && opt_l > 0)
    printf("%4s  %-9s %12s  %-6s  %-12s\n","No","Time", "packetNo","pid","type");

  if ( opt_l >= vd->lineC ) {
    printf("%4d %12s %10d  0x%04x  %-12s\n",
           vd->lineC, pcr2str(pcr), pcc, pid, type);
  } else if ( vd->snip == false && opt_l > 0 ) {
    printf("...\n" );
    vd->snip = true ;
  }
  vd->lineC++;

  return false ;                /* skip() の値 */
}

/*
 *  buf 中のパケットが空か？ ture = 空
 */
bool packetEmpty( ReadBuf *rb )
{
  /* resync の為に 8パケット分を残して buff の補充   */
  if ( ( rb->curr + rb->unit_size * 8 ) > rb->tail )
    read_buf( rb ); 

  if ( rb->eof == false ) return false ;
  if ( rb->tail >= ( rb->curr + rb->unit_size)) return false ;
  return true;
}


/*
 *  buf にデータの読み込み
 */
int read_buf( ReadBuf *rb ) {
  int n,m ;

  if ( rb->eof == false ) {
    n = rb->tail - rb->curr;
    if( n > 0) {
      memcpy(rb->buf, rb->curr, n);
    }
    m = read(rb->fd, rb->buf+n, rb->bufsize - n);
    if ( m == 0 ) {
      rb->eof = true ;
    } else if ( m < 0 ) {
      printf("Error: read_buf() %s\n", strerror(errno));
      exit(-1);
    } else {
      n += m;
      rb->total += m;
    }
    rb->curr = rb->buf ;
    rb->tail = rb->buf + n ;
  }
  return n;
}

/*
 * syncbyte の検索(ファイルの読み込み付き)
 */
unsigned char *resyncRB(ReadBuf *rb )
{
  int i;
  unsigned char *tail;

  while ( packetEmpty( rb ) == false ) {
    if( *rb->curr == 0x47 ){
      tail = rb->curr + rb->unit_size * 8 ;
      if ( rb->tail > tail ) {
        for(i=1;i<8;i++){
          if( *(rb->curr + rb->unit_size*i) != 0x47) {
            break;
          }
        }
        if(i == 8) {
          return rb->curr;
        }
      }
    }
    rb->curr += 1;
  }

  return NULL;
}

/*
 *  PCRの取得(baseのみ)
 */
INT64  getPCR(TS_HEADER *hdr, ADAPTATION_FIELD *adapt )
{
  INT64  ret ;
  if(hdr->transport_error_indicator == 0){
    if( hdr->adaptation_field_control & 0x02) {
      if(adapt->pcr_flag == 1) {
        ret = ( adapt->program_clock_reference >> 9  ) & 0x1ffffffff ;
        return ret;
      }
    }
  }
  return 0;
}

/*
 *  時刻に変換
 */
char *pcr2str( INT64  pcr  )
{
  INT64         ext,base1,base2;
  int           hour,min,sec,sec2;
  static char   buff[32];

  base1 = pcr;
  base1 = base1 / 90;       // 90kHz * 1000
  base2 = base1 / 1000;     // 90kHz 

  //  printf("%02ld\n", base2 );
  
  hour = base2 / 3600 ;
  min = ( base2 % 3600) / 60;
  sec = base2 % 60 ;
  sec2 = ( base1 - (( hour * 3600 )+( min * 60 )+ sec) * 1000) / 10 ;
  if ( opt_S == true ) {
    sprintf(buff, "%d:%02d:%02d", hour,min,sec);
  } else {
    sprintf(buff, "%02d:%02d:%02d.%02d", hour,min,sec,sec2);
  }

  return buff ;
}

/*
 *  -s オプションの判定  true = skip する
 */
bool skip( INT64  pcr  )
{
  if ( opt_s > 0 ) {
    int t = pcr / 90000 ;
    if ( t < opt_s ) {
      //printf("%ld %d\n",pcr, t);
      return true;
    }
  }
  return false;
}

