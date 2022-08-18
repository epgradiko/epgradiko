#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <string.h>
#include <getopt.h>
#include <iconv.h>
#include <time.h>
#include <ctype.h>

#include "util.h"
#include "eit.h"
#include "ts.h"
#include "psi.h"
#include "sdtt.h"
#include "tot.h"
#include "dsmcc.h"
#include "bit.h"
#include "clt2png.h"
#include "ts_ctl.h"

#define tran_genre(n) ((n)==16 ? 0 : ((n)+1))

#if KATAUNA
extern void shobo_( char *, char *, int, int );
extern void free_syobo( void );
#endif
extern void	conv_title_subtitle(EIT_CONTROL *);
extern void dumpSDT(unsigned char *, SVT_CONTROL *, char *, int, int);
extern void dumpEIT( unsigned char *, SVT_CONTROL *, int, int );
extern void dumpCDT(unsigned char *, SVT_CONTROL * );
#if ENABLE_ERT
extern void dumpERT(unsigned char *, SVT_CONTROL * );
#endif


#if REC10
typedef		struct	_ContentTYPE{
	char	*japanese ;
	char	*english ;
}CONTENT_TYPE;

#define		CAT_COUNT		16
static  CONTENT_TYPE	ContentCatList[] = {
	{ "ニュース・報道", "news" },
	{ "スポーツ", "sports" },
	{ "情報", "information" },
	{ "ドラマ", "drama" },
	{ "音楽", "music" },
	{ "バラエティ", "variety" },
	{ "映画", "cinema" },
	{ "アニメ・特撮", "anime" },
	{ "ドキュメンタリー・教養", "documentary" },
	{ "演劇", "stage" },
	{ "趣味・実用", "hobby" },
	{ "福祉", "welfare" },
	{ "予備", "etc" }, //予備
	{ "予備", "etc" }, //予備
	{ "拡張", "expand" }, //予備
	{ "その他", "etc" }, //その他
	{ "16", "" }		//無指定(処理用に拡張)
};
#endif

SVT_CONTROL	*svttop = NULL;
DSM_CONTROL	dsmctl[1024];
#define		SECCOUNT	64
static unsigned char *base64 = (unsigned char *)"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";

static void base64_char(unsigned long bb, int srclen, unsigned char *dest, int j)
{
	int x, i, base;

	/* 最終位置の計算 */
	for ( i = srclen; i < 2; i++ ) 
		bb <<= 8;

	/* BASE64変換 */
	for ( base = 18, x = 0; x < srclen + 2; x++, base -= 6) {
		dest[j++] = base64[ (unsigned long)((bb>>base) & 0x3F) ];
	}

	/* 端数の判断 */
	for ( i = x; i < 4; i++ ) {
		dest[j++] = (unsigned char)'=';		/* 端数 */
	}
	
}

static void base64_encode(unsigned char *dest, const unsigned char *src, int len)
{
	unsigned char *p = (unsigned char *)src;
	unsigned long bb = (unsigned long)0;
	int i = 0, j = 0;

	while (len--)
	{
		bb <<= 8;
		bb |= (unsigned long)*p;

		/* 24bit単位に編集 */
		if (i == 2) {
			base64_char(bb, i, dest, j);

			j = j + 4;
			i = 0;
			bb = 0;
		} else
			i++;

		p++;
	}

	/* 24bitに満たない場合 */
	if (i) base64_char(bb, i - 1, dest, j);

}

void	xmlspecialchars(char *str)
{
	strrep(str, "&", "&amp;");
	strrep(str, "'", "&apos;");
	strrep(str, "\"", "&quot;");
	strrep(str, "<", "&lt;");
	strrep(str, ">", "&gt;");
}


void	GetSDT(FILE *infile, SVT_CONTROL *svttop, SECcache *secs, int count, char *header, int is_logo, int select_sid, int sdt_mode, int eit_mode)
{
	SECcache  *bsecs;
	int pmtpids[SECCOUNT];

	memset(pmtpids, 0, sizeof(pmtpids));
	int dsmccpids[SECCOUNT];
	memset(dsmccpids, 0, sizeof(dsmccpids));
	int i = 0 , downloadDataId = 0;

	while((bsecs = readTS(infile, secs, count)) != NULL) {
		switch( bsecs->pid & 0xFF ){
			/* SDT */
			case 0x11:
				dumpSDT(bsecs->buf, svttop, header, select_sid, sdt_mode);
				break;
			/* EIT 0x26,0x27は移動体用*/
			case 0x12:
				dumpEIT( bsecs->buf, svttop, select_sid, eit_mode );
				break;
#if ENABLE_1SEG
//			case 0x26:
			case 0x27:
				if( sdt_mode )
					dumpEIT( bsecs->buf, svttop, select_sid, eit_mode|0x02 );
				break;
#endif
			/* TOT */
			case 0x14:
				dumpTOT(bsecs->buf);
				break;
			/* SDTT */
			//case 0x23:
			//	dumpSDTT( bsecs->buf, svttop );
			//	break;
			/* BIT */
			case 0x24:
				dumpBIT(bsecs->buf);
				break;
#if ENABLE_ERT
			/* ERT  */
			case 0x21:
				dumpERT( bsecs->buf, svttop );
				break;
#endif
			/* CDT */
			case 0x29:
				dumpCDT(bsecs->buf, svttop );
				break;
			default:
				if ( is_logo ) {
					/* PAT */
					if((bsecs->pid & 0xFF) == 0x00) {
						dumpPAT(bsecs->buf, secs, count, pmtpids);
					}
					/* PMT */
					for ( i = 1; i < SECCOUNT; i++ ) {
						if ( pmtpids[i] == 0 ) {
							break;
						}
						/* PMT specified by PAT */
						if ( bsecs->pid == pmtpids[i] ) {
							dumpPMT(bsecs->buf, secs, count, dsmccpids);
						}
					}
					/* DSM-CC */
					for ( i = 0; i < SECCOUNT; i++ ) {
						if ( dsmccpids[i] == 0 ) {
							break;
						}
						/* DSM-CC specified by PMT */
						if ( bsecs->pid == dsmccpids[i] ) {
							dumpDSMCC(bsecs->buf, &downloadDataId, dsmctl);
						}
					}
				}
				break;
		}
	}
}


EIT_CONTROL	*free_EIT_CONTROL( EIT_CONTROL *eitcur )
{
	EIT_CONTROL	*eitnext ;

	eitnext = eitcur->next ;
	free(eitcur->title);
	free(eitcur->subtitle);
#if REC10
	if( eitcur->desc != NULL )
		free(eitcur->desc);
#endif
	free(eitcur);
	return eitnext ;
}


#if 0
int cut_sid_scan( int *cut_sids, int sid )
{
	while( *cut_sids ){
		if( *cut_sids == sid )
			return 0;
		cut_sids++;
	}
	return 1;
}


void svt_slim( SVT_CONTROL *svttop, int select_sid, int *cut_sids )
{
	SVT_CONTROL	*svtcur;
	SVT_CONTROL	*tmp;

	svtcur = svttop->next;
	while( svtcur ){
		if( ( select_sid==0 && cut_sid_scan( cut_sids, svtcur->service_id ) ) || ( select_sid && select_sid==svtcur->service_id ) ){
			svtcur->eitsch = calloc( 1, sizeof(EIT_CONTROL) );
			svtcur->eit_pf = calloc( 1, sizeof(EIT_CONTROL) );
			svtcur         = svtcur->next;
		}else{
			tmp       = svtcur->prev;
			tmp->next = svtcur->next;
			if( tmp->next != NULL )
				tmp->next->prev = tmp;
			free( svtcur );
			svtcur = tmp->next;
		}
	}
}


void	GetEIT(FILE *infile, SVT_CONTROL *svttop, SECcache *secs, int mode )
{
	SECcache  *bsecs;

	memset(secs, 0,  sizeof(SECcache) * SECCOUNT);
	secs[0].pid = 0x12; /* H-EIT  */
//	secs[1].pid = 0x26; /* M-EIT  */
//	secs[2].pid = 0x27; /* L-EIT  */

	fseek(infile, 0, SEEK_SET);
	while((bsecs = readTS(infile, secs, 1)) != NULL) {
		/* EIT 0x26,0x27は移動体用*/
		switch( bsecs->pid & 0xFF ){
			case 0x12:
//			case 0x26:
//			case 0x27:
				dumpEIT( bsecs->buf, svttop, mode );
				break;
		}
	}
}
#endif


// extern SVT_CONTROL *serachid(SVT_CONTROL *, int);
extern void enqueue_sdt(SVT_CONTROL *, SVT_CONTROL *);


void svt_init( SVT_CONTROL *svttop, int *cut_sids )
{
	SVT_CONTROL	*svtcur;

	while( *cut_sids ){
		svtcur = calloc(1, sizeof(SVT_CONTROL));
		svtcur->service_id   = *cut_sids++;
		svtcur->service_type = 0x00;
		svtcur->eitsch       = calloc( 1, sizeof(EIT_CONTROL) );
		svtcur->eit_pf       = calloc( 1, sizeof(EIT_CONTROL) );
		svtcur->import_stat  = -1;
		enqueue_sdt( svttop, svtcur );
	}
}


int svt_assortment( SVT_CONTROL *svttop )
{
	SVT_CONTROL		*svtcur = svttop;
	SVT_CONTROL		*sdt_tmp;
	EIT_CONTROL		*eit_tmp;
	int				sdt_cnt = 0;

	while( svtcur->next != NULL ){
		svtcur = svtcur->next;
		if( svtcur->import_stat <= 0 ){
			eit_tmp = svtcur->eitsch;
			do{
				eit_tmp = free_EIT_CONTROL( eit_tmp );
			}while( eit_tmp != NULL );
			eit_tmp = svtcur->eit_pf;
			do{
				eit_tmp = free_EIT_CONTROL( eit_tmp );
			}while( eit_tmp != NULL );
			sdt_tmp       = svtcur->prev;
			sdt_tmp->next = svtcur->next;
			if( sdt_tmp->next != NULL )
				sdt_tmp->next->prev = sdt_tmp;
			free( svtcur );
			svtcur = sdt_tmp;
		}else{
			if( svtcur->import_stat == 1 ){
				eit_tmp = svtcur->eitsch->next;
				svtcur->eitsch->next = NULL;
				while( eit_tmp != NULL ){
					eit_tmp = free_EIT_CONTROL( eit_tmp );
				}
				eit_tmp = svtcur->eit_pf->next;
				svtcur->eit_pf->next = NULL;
				while( eit_tmp != NULL ){
					eit_tmp = free_EIT_CONTROL( eit_tmp );
				}
			}
			sdt_cnt++;
		}
	}
	return sdt_cnt;
}


void pf_regulation( SVT_CONTROL *svttop )
{
	SVT_CONTROL *svtcur;
	EIT_CONTROL	*main_pf;
	EIT_CONTROL	*sub_pf;

	while( svttop ){
		main_pf = svttop->eit_pf;
		while( main_pf->next ){
			main_pf = main_pf->next;
			svtcur  = svttop;
			while( svtcur->next ){
				svtcur = svtcur->next;
				if( svtcur->service_type == 0x01 ){
					sub_pf = svtcur->eit_pf;
					while( sub_pf->next ){
						sub_pf = sub_pf->next;
						if( main_pf->yy==sub_pf->yy && main_pf->mm==sub_pf->mm && main_pf->dd==sub_pf->dd
							&&  main_pf->hh==sub_pf->hh && main_pf->hm==sub_pf->hm && main_pf->ss==sub_pf->ss
							&&  main_pf->duration==sub_pf->duration
							&& ( sub_pf->event_id==main_pf->event_id || !strcmp( sub_pf->title, main_pf->title ) )
						){
							EIT_CONTROL	*tmp = sub_pf->prev;
							tmp->next = sub_pf->next;
							sub_pf    = free_EIT_CONTROL( sub_pf );
							if( sub_pf ){
								sub_pf->prev = tmp;
								sub_pf       = tmp;
							}else
								break;
						}
					}
				}
			}
		}
		svttop = svttop->next;
	}
}


void insertRest_pf( EIT_CONTROL *eitcur )
{
	EIT_CONTROL	*tmp;
	time_t		start_time ;
	time_t		end_time ;
	struct tm	*endtl ;

	if( eitcur && eitcur->event_status==CERTAINTY ){
		end_time = timeParse( eitcur ) + eitcur->duration;
		while( eitcur->next ){
			eitcur = eitcur->next;
			if( eitcur->event_status & START_TIME_UNCERTAINTY )
				return;
			start_time = timeParse( eitcur );
			if( start_time != end_time ){
				tmp             = calloc(1, sizeof(EIT_CONTROL));
				tmp->prev       = eitcur->prev;
				tmp->next       = eitcur;
				eitcur->prev    = tmp;
				tmp->prev->next = tmp;
				tmp->event_id   = -1;
				tmp->servid     = eitcur->servid;
				tmp->table_id   = eitcur->table_id;
				tmp->title      = calloc( 1, 4*3+1 );
				memcpy( tmp->title, "放送休止", 4*3 );
				tmp->subtitle        = calloc(1, 1);
				endtl                = localtime(&end_time);
				tmp->yy              = endtl->tm_year;
				tmp->mm              = endtl->tm_mon + 1;
				tmp->dd              = endtl->tm_mday;
				tmp->hh              = endtl->tm_hour;
				tmp->hm              = endtl->tm_min;
				tmp->ss              = endtl->tm_sec;
				tmp->duration        = start_time - end_time;
				tmp->content_type    = 14;
				tmp->content_subtype = 0x3FU;
				tmp->genre2          = 16;
				tmp->sub_genre2      = 16;
				tmp->genre3          = 16;
				tmp->sub_genre3      = 16;
			}
			if( eitcur->event_status & DURATION_UNCERTAINTY )
				return;
			end_time = start_time + eitcur->duration;
		}
	}
}


extern EIT_NULLSEGMENT *search_eitnull( int, int, int, int );

int	eitnull_chk( EIT_CONTROL *eitcur )
{
	EIT_NULLSEGMENT	*eit_null;
	EIT_CONTROL		*prev_eit = eitcur->prev;

	if(  eitcur->prev->version_number == eitcur->version_number  ){
		// 同テーブル確認
		if( prev_eit->table_id == eitcur->table_id ){
			// セクション内または連続確認
			if( prev_eit->section_number==eitcur->section_number || prev_eit->section_number+1==eitcur->section_number )
				return TRUE;
			else
				// 空セグメント存在の可能性確認
				if( prev_eit->section_number==prev_eit->segment_last_section_number && (eitcur->section_number&0x07)==0x00 ){
					// 空セグメント確認
					for( int sec_loop=(prev_eit->section_number&0xF8)+8; sec_loop<eitcur->section_number; sec_loop+=8 ){
						eit_null = search_eitnull( prev_eit->servid, prev_eit->table_id, sec_loop, -1 );
						if( eit_null==NULL || eit_null->version_number!=prev_eit->version_number )
							return FALSE;
					}
				}else
					return FALSE;
		}else
			// 空セグメント存在の可能性確認
			if( prev_eit->section_number==prev_eit->segment_last_section_number && (eitcur->section_number&0x07)==0x00 ){
				// 空セグメント確認
				for( int tbl_loop=prev_eit->table_id; tbl_loop<=eitcur->table_id; tbl_loop++ ){
					int sec_loop = tbl_loop==prev_eit->table_id ? (prev_eit->section_number&0xF8)+8 : 0;
					int loop_end = tbl_loop==eitcur->table_id ? eitcur->section_number : 32;
					for( ; sec_loop<loop_end; sec_loop+=8 ){
						eit_null = search_eitnull( prev_eit->servid, tbl_loop, sec_loop, -1 );
						if( eit_null==NULL || eit_null->version_number!=prev_eit->version_number )
							return FALSE;
					}
				}
			}else
				return FALSE;
		return (eitcur->prev->import_cnt+1==eitcur->import_cnt ? TRUE : FALSE);
	}else
		return FALSE;
}


void insertRest_sch( SVT_CONTROL *svtcur )
{
	EIT_CONTROL *eitcur = svtcur->eitsch->next;
	EIT_CONTROL	*tmp;
	time_t		start_time ;
	time_t		end_time ;
	struct tm	*endtl ;

	if( eitcur ){
		end_time = timeParse( eitcur ) + eitcur->duration;
		while( eitcur->next ){
			eitcur     = eitcur->next;
			start_time = timeParse( eitcur );
			if( end_time < start_time ){
				int		same_segment;

/*
				if( eitcur->renew_cnt>0 && eitcur->prev->renew_cnt>0 ){
					same_segment =  eitcur->import_cnt==eitcur->prev->import_cnt+1 ? TRUE : FALSE;
				}else{
					goto NEXT_EIT;
				}
*/
				same_segment = eitnull_chk( eitcur );
				if( 1 ){
					tmp             = calloc(1, sizeof(EIT_CONTROL));
					tmp->prev       = eitcur->prev;
					tmp->next       = eitcur;
					eitcur->prev    = tmp;
					tmp->prev->next = tmp;
					tmp->event_id   = -1;
					tmp->servid     = eitcur->servid;
					tmp->table_id   = eitcur->table_id;
					tmp->title      = calloc( 1, 4*3+1 );
					memcpy( tmp->title, "放送休止", 4*3 );
					tmp->subtitle        = calloc(1, 1);
					endtl                = localtime(&end_time);
					tmp->yy              = endtl->tm_year;
					tmp->mm              = endtl->tm_mon + 1;
					tmp->dd              = endtl->tm_mday;
					tmp->hh              = endtl->tm_hour;
					tmp->hm              = endtl->tm_min;
					tmp->ss              = endtl->tm_sec;
					tmp->duration        = start_time - end_time;
					tmp->content_type    = 14;
					tmp->content_subtype = same_segment ? 0x3FU : 14;
					tmp->genre2          = 16;
					tmp->sub_genre2      = 16;
					tmp->genre3          = 16;
					tmp->sub_genre3      = 16;
				}
			}
NEXT_EIT:
			end_time = start_time + eitcur->duration;
		}
	}
}

// ジャンル未定義への対応
void rest_repair( EIT_CONTROL *eitcur )
{
	if( eitcur->content_type==0 && eitcur->content_subtype==0 && eitcur->genre2==0 && eitcur->sub_genre2==0 && eitcur->genre3==0 && eitcur->sub_genre3==0 ){
		eitcur->content_type    = 14;
		eitcur->content_subtype = 0x3FU;
		eitcur->genre2          = 16;
		eitcur->sub_genre2      = 16;
		eitcur->genre3          = 16;
		eitcur->sub_genre3      = 16;
	}
	if( eitcur->content_type==14 && eitcur->content_subtype==0x3FU && *eitcur->title=='\0' ){
		free( eitcur->title );
		eitcur->title = malloc( 13 );
		strcpy( eitcur->title, "放送休止" );
	}
}


void	dumpXML( FILE *outfile, SVT_CONTROL *svtcur, int mode )
{
	EIT_CONTROL	*eitcur;
	EIT_CONTROL	*eitsch = svtcur->eitsch;
	EIT_CONTROL	*eit_pf = svtcur->eit_pf;
	EIT_CONTROL	*tmp;
	time_t		start_time ;
	time_t		end_time ;
	struct tm	*endtl ;
	char		cendtime[32];
	char		cstarttime[32];
	char		title[MAXSECLEN];
	char		subtitle[MAXSECLEN];
#if REC10
	char		desc[102400] = {0};
	char		VideoType[1024];
	char		AudioType[1024];
#endif
	char		*tag;
	char		*wrt_buf;
	char		*wrt_pnt;
	int			sch_cnt = 0;
	int			pf_cnt = 0;
	int			put_cnt = 0;
//	int			type = memcmp( svtcur->ontv, "GR", 2 );

	insertRest_pf( eit_pf->next );
	if( !mode ){
#if 0
		// 時系列が連続してない番組の削除
		if( eitsch->next!=svtcur->start_eit || eitsch->next->event_id!=svtcur->start_eid || eitsch->next->servid!=svtcur->start_sid ){
			tmp = searcheit( eitsch->next, svtcur->start_sid, svtcur->start_eid );
			if( tmp!=NULL && tmp==svtcur->start_eit ){
				if( timeParse( tmp ) - timeParse( tmp->prev ) != tmp->prev->duration ){
					tmp->prev->next = NULL;
					do{
						tmp = free_EIT_CONTROL( tmp );
					}while( tmp != NULL );
				}
			}
		}
#endif
		// 放送休止の補完
		insertRest_sch( svtcur );
		tmp    = eit_pf;
		eitcur = eitsch;
		while( tmp->next != NULL ){
			tmp = tmp->next;
			while(1){
				if( eitcur->next ){
					eitcur = eitcur->next;
					if( eitcur->event_id == tmp->event_id ){
						// 冒頭の余分なschをpf1つ前を残して切り捨て
						if( pf_cnt==0 && sch_cnt>1 ){
							EIT_CONTROL		*work = eitsch->next;
							eitcur->prev->prev->next = NULL;
							do{
								work = free_EIT_CONTROL( work );
							}while( work != NULL );
							eitsch->next       = eitcur->prev;
							eitcur->prev->prev = eitsch;
							sch_cnt = 1;
						}
						tmp->sch_pnt = sch_cnt;
						sch_cnt++;
						break;
					}
				}else{
					eitcur       = eitsch;
					sch_cnt      = 0;
					tmp->sch_pnt = -1;	// 未発見
					break;
				}
				sch_cnt++;
			}
			pf_cnt++;
		}
		sch_cnt = 0;
		tmp->next = eitsch->next;
	}else{
		tmp = eit_pf;
		while( tmp->next != NULL ){
			tmp          = tmp->next;
			tmp->sch_pnt = -2;	// 未調査
			pf_cnt++;
		}
	}
	eitcur = eit_pf->next;
	free( eit_pf );
	free( eitsch );

	wrt_pnt = malloc( 4*1024 );
	while(eitcur != NULL){
		if(!eitcur->servid){
			eitcur = eitcur->next ;
			continue ;
		}
		wrt_buf = wrt_pnt;
		rest_repair( eitcur );
		conv_title_subtitle( eitcur );		// eit.cから移動 但し関数を別途作成した為、オリジナル関数でのデバッグは未実施
		memset(title, '\0', sizeof(title));
#if 0	// 有料番組フラグ
		if( eitcur->free_CA_mode ){
			memcpy( title, "[￥]", 5 );
			strcpy( title+5, eitcur->title );
		}else
			strcpy( title, eitcur->title );
#else
		strcpy(title, eitcur->title);
#endif
		xmlspecialchars(title);

		memset(subtitle, '\0', sizeof(subtitle));
		strcpy(subtitle, eitcur->subtitle);
		xmlspecialchars(subtitle);
#if REC10
		if( eitcur->desc ){
			memset(desc, '\0', sizeof(desc));
			strcpy(desc, eitcur->desc);
			xmlspecialchars(desc);
		}else
			desc[0] = '\0';
		memset(VideoType, '\0', sizeof(VideoType));
		strcpy(VideoType, parseComponentDescType(eitcur->video_type));
		xmlspecialchars(VideoType);

		memset(AudioType, '\0', sizeof(AudioType));
		strcpy(AudioType, parseAudioComponentDescType(eitcur->audio_type));
		xmlspecialchars(AudioType);
#endif
		start_time = timeParse( eitcur );
		end_time   = start_time + eitcur->duration;
		endtl      = localtime(&end_time);
		memset(cendtime, '\0', sizeof(cendtime));
		memset(cstarttime, '\0', sizeof(cstarttime));
		tag = put_cnt>=pf_cnt ? "programme" : "programme_pf";
#if REC10
		//rec10
		strftime(cendtime, (sizeof(cendtime) - 1), "%Y%m%d%H%M%S", endtl);
		sprintf( cstarttime, "%4i%02i%02i%02i%02i%02i", eitcur->yy+1900, eitcur->mm, eitcur->dd, eitcur->hh, eitcur->hm, eitcur->ss );
		wrt_buf += sprintf( wrt_buf, "  <%s start=\"%s +0900\" stop=\"%s +0900\" channel=\"%s\" event=\"%i\">\n", tag, cstarttime, cendtime, svtcur->ontv, eitcur->event_id);
		wrt_buf += sprintf( wrt_buf, "    <title lang=\"ja_JP\">%s</title>\n", title);
		wrt_buf += sprintf( wrt_buf, "    <desc lang=\"ja_JP\">%s</desc>\n", subtitle);
		wrt_buf += sprintf( wrt_buf, "    <longdesc lang=\"ja_JP\">%s</longdesc>\n", desc);
		wrt_buf += sprintf( wrt_buf, "    <category lang=\"ja_JP\">%s</category>\n", ContentCatList[eitcur->content_type].japanese);
//		wrt_buf += sprintf( wrt_buf, "    <category lang=\"en\">%s</category>\n", ContentCatList[eitcur->content_type].english);
		wrt_buf += sprintf( wrt_buf, "    <video type=\"%i\">%s</video>\n", eitcur->video_type, VideoType);
		wrt_buf += sprintf( wrt_buf, "    <audio type=\"%i\" multi=\"%i\">%s</audio>\n", eitcur->audio_type, eitcur->multi_type, AudioType);
#else
		//epgrec una
		strftime(cendtime, (sizeof(cendtime) - 1), "%Y-%m-%d %H:%M:%S", endtl);
		sprintf( cstarttime, "%4i-%02i-%02i %02i:%02i:%02i", eitcur->yy+1900, eitcur->mm, eitcur->dd, eitcur->hh, eitcur->hm, eitcur->ss );
		wrt_buf += sprintf( wrt_buf, "  <%s start=\"%s\" stop=\"%s\" channel=\"%s\" eid=\"%i\">\n", tag, cstarttime, cendtime, svtcur->ontv, eitcur->event_id);
		wrt_buf += sprintf( wrt_buf, "    <title>%s</title>\n    <desc>%s</desc>\n", title, subtitle );
		wrt_buf += sprintf( wrt_buf, "    <genres>%i:%i:%i:%i:%i:%i</genres>\n", tran_genre(eitcur->content_type), eitcur->content_subtype,
													tran_genre(eitcur->genre2), eitcur->sub_genre2, tran_genre(eitcur->genre3), eitcur->sub_genre3);
		wrt_buf += sprintf( wrt_buf, "    <video_audio>%i:%i:%i</video_audio>\n", eitcur->video_type, eitcur->audio_type, eitcur->multi_type );
//		wrt_buf += sprintf( wrt_buf, "    <title>%s</title>\n    <desc>%s</desc>\n    <eid>%i</eid>\n", title, subtitle, eitcur->event_id);
//		wrt_buf += sprintf( wrt_buf, "    <category>%i</category><sub_genre>%i</sub_genre>\n", tran_genre(eitcur->content_type), eitcur->content_subtype);
//		wrt_buf += sprintf( wrt_buf, "    <genre2>%i</genre2><sub_genre2>%i</sub_genre2>\n", tran_genre(eitcur->genre2), eitcur->sub_genre2);
//		wrt_buf += sprintf( wrt_buf, "    <genre3>%i</genre3><sub_genre3>%i</sub_genre3>\n", tran_genre(eitcur->genre3), eitcur->sub_genre3);
//		wrt_buf += sprintf( wrt_buf, "    <video_type>%i</video_type><audio_type>%i</audio_type><multi_type>%i</multi_type>\n", eitcur->video_type, eitcur->audio_type, eitcur->multi_type );
#endif
		if( put_cnt++ < pf_cnt )
			wrt_buf += sprintf( wrt_buf, "    <status>%i</status>\n    <sch_pnt>%i</sch_pnt>\n", eitcur->event_status, eitcur->sch_pnt );
		else
			sch_cnt++;
		wrt_buf += sprintf( wrt_buf, "  </%s>\n", tag );
		fwrite( wrt_pnt, wrt_buf-wrt_pnt, 1, outfile );
		eitcur   = free_EIT_CONTROL( eitcur );
	}
	free( wrt_pnt );
	if( pf_cnt || sch_cnt )
		fprintf( outfile, "<programme_cnt><disc>%s</disc><pf_cnt>%i</pf_cnt><sch_cnt>%i</sch_cnt></programme_cnt>\n", svtcur->ontv, pf_cnt, sch_cnt );
}


int line_serial( char *wrt_buf, int line_cnt, int array_cnt, EIT_CONTROL *eitcur, char *ch_disc  )
{
	time_t		start_time ;
	time_t		end_time ;
	struct tm	*endtl ;
	char		cendtime[32];
	char		cstarttime[32];
	char		*title;
	char		mark[2048];
#if REC10
	char		desc[102400] = {0};
#endif

	rest_repair( eitcur );
	conv_title_subtitle( eitcur );		// eit.cから移動 但し関数を別途作成した為、オリジナル関数でのデバッグは未実施
	title = eitcur->title;

	char	subtitle[MAXSECLEN];
	char	*src_pnt = eitcur->subtitle;
	char	*dest_pnt = subtitle;

#if KATAUNA	// 有料番組フラグ
	if( eitcur->free_CA_mode ){
		memcpy( dest_pnt, "[￥]", 5 );
		dest_pnt += 5;
	}
#endif
	// シリアライズ用エスケープ処理
	while( *src_pnt ){
		switch( *src_pnt ){
			case '\\':
				*dest_pnt++ = '\\';
				*dest_pnt++ = '\\';
				break;
			case '\n':
				*dest_pnt++ = '\\';
				*dest_pnt++ = 'n';
				break;
			default:
				*dest_pnt++ = *src_pnt;
				break;
		}
		src_pnt++;
	}
	*dest_pnt = '\0';

	int	str_offset;
	int	str_len;
	int	mv_len;
	memset(mark, '\0', sizeof(mark));
	str_offset = strlen(title);
	if( *( title + str_offset - 1) == ']') {
		str_len = 0;
		while( str_offset - 1 - str_len - 1 > 0 ){
			if( *( title + str_offset - 1 - str_len - 1) == '[' ){
				mv_len = 1 + str_len + 1;
				strcat( mark, title + str_offset - mv_len);
				*( title + str_offset - mv_len ) = '\0';
				str_offset = strlen(title);
				str_len = 0;
			} else {
				str_len++;
			}
		}
	}

#if REC10
	if( eitcur->desc ){
		memset(desc, '\0', sizeof(desc));
		strcpy(desc, eitcur->desc);
	}else
		desc[0] = '\0';
#endif
	start_time = timeParse( eitcur );
	end_time   = start_time + eitcur->duration;
	endtl      = localtime(&end_time);
	memset(cendtime, '\0', sizeof(cendtime));
	strftime(cendtime, (sizeof(cendtime) - 1), "%Y-%m-%d %H:%M:%S", endtl);
	sprintf( cstarttime, "%4i-%02i-%02i %02i:%02i:%02i", eitcur->yy+1900, eitcur->mm, eitcur->dd, eitcur->hh, eitcur->hm, eitcur->ss );

	return sprintf( wrt_buf,
		"i:%i;a:%i:{"
		"s:9:\"starttime\";s:19:\"%s\";"
		"s:7:\"endtime\";s:19:\"%s\";"
		"s:12:\"channel_disc\";s:%i:\"%s\";"
		"s:3:\"eid\";i:%i;"
		"s:5:\"title\";s:%i:\"%s\";"
		"s:4:\"desc\";s:%i:\"%s\";"
		"s:4:\"mark\";s:%i:\"%s\";"
		"s:12:\"free_CA_mode\";i:%i;"
		"s:8:\"category\";i:%i;s:9:\"sub_genre\";i:%i;"
		"s:6:\"genre2\";i:%i;s:10:\"sub_genre2\";i:%i;"
		"s:6:\"genre3\";i:%i;s:10:\"sub_genre3\";i:%i;"
		"s:10:\"video_type\";i:%i;s:10:\"audio_type\";i:%i;s:10:\"multi_type\";i:%i;",
		line_cnt, array_cnt,
		cstarttime,
		cendtime,
		strlen(ch_disc), ch_disc,
		eitcur->event_id,
		strlen(title), title,
		dest_pnt-subtitle, subtitle,
		strlen(mark), mark,
		eitcur->free_CA_mode,
		tran_genre(eitcur->content_type), eitcur->content_subtype,
		tran_genre(eitcur->genre2), eitcur->sub_genre2,
		tran_genre(eitcur->genre3), eitcur->sub_genre3,
		eitcur->video_type, eitcur->audio_type, eitcur->multi_type
	);
}


void	dumpSERIAL( FILE *outfile, SVT_CONTROL *svtcur, int mode )
{
	EIT_CONTROL	*eitcur;
	EIT_CONTROL	*eitsch = svtcur->eitsch;
	EIT_CONTROL	*eit_pf = svtcur->eit_pf;
	EIT_CONTROL	*tmp;
	char		*wrt_buf;
	char		*wrt_pnt;
	int			sch_cnt;
	int			pf_cnt = 0;
//	int			type = memcmp( svtcur->ontv, "GR", 2 );

	insertRest_pf( eit_pf->next );
	if( !mode ){
		// 放送休止の補完
		insertRest_sch( svtcur );
		tmp     = eit_pf;
		eitcur  = eitsch;
		sch_cnt = 0;
		while( tmp->next != NULL ){
			tmp = tmp->next;
			while(1){
				if( eitcur->next ){
					eitcur = eitcur->next;
					if( eitcur->event_id == tmp->event_id ){
						// 冒頭の余分なschをpf1つ前を残して切り捨て
						if( pf_cnt==0 && sch_cnt>1 ){
							EIT_CONTROL		*work = eitsch->next;
							eitcur->prev->prev->next = NULL;
							do{
								work = free_EIT_CONTROL( work );
							}while( work != NULL );
							eitsch->next       = eitcur->prev;
							eitcur->prev->prev = eitsch;
							sch_cnt = 1;
						}
						tmp->sch_pnt = sch_cnt;
						sch_cnt++;
						break;
					}
				}else{
					eitcur       = eitsch;
					sch_cnt      = 0;
					tmp->sch_pnt = -1;	// 未発見
					break;
				}
				sch_cnt++;
			}
			pf_cnt++;
		}
	}else{
		tmp = eit_pf;
		while( tmp->next != NULL ){
			tmp          = tmp->next;
			tmp->sch_pnt = -2;	// 未調査
			pf_cnt++;
		}
	}

	sch_cnt = 0;
	tmp     = eitsch;
	while( tmp->next != NULL ){
		tmp = tmp->next;
		sch_cnt++;
	}

	if( pf_cnt || sch_cnt )
		fprintf( outfile, "a:3:{s:4:\"disc\";s:%i:\"%s\";s:6:\"pf_cnt\";i:%i;s:7:\"sch_cnt\";i:%i;}\n", strlen(svtcur->ontv), svtcur->ontv, pf_cnt, sch_cnt );
	else
		return;
	wrt_pnt = malloc( 4*1024 );
	if( pf_cnt ){
		eitcur = eit_pf->next;
		fprintf( outfile, "a:%i:{", pf_cnt );
		pf_cnt = 0;
		while( eitcur != NULL ){
			wrt_buf  = wrt_pnt;
			wrt_buf += line_serial( wrt_buf, pf_cnt, 19, eitcur, svtcur->ontv );
			wrt_buf += sprintf( wrt_buf, "s:6:\"status\";i:%i;s:7:\"sch_pnt\";i:%i;}", eitcur->event_status, eitcur->sch_pnt );
			fwrite( wrt_pnt, wrt_buf-wrt_pnt, 1, outfile );
			pf_cnt++;
			eitcur = free_EIT_CONTROL( eitcur );
		}
		fwrite( "}\n", 2, 1, outfile );
	}
	free( eit_pf );
	if( sch_cnt ){
		eitcur = eitsch->next;
		fprintf( outfile, "a:%i:{", sch_cnt );
		sch_cnt = 0;
		while( eitcur != NULL ){
			wrt_buf    = wrt_pnt;
			wrt_buf   += line_serial( wrt_buf, sch_cnt, 17, eitcur, svtcur->ontv );
			*wrt_buf++ = '}';
			fwrite( wrt_pnt, wrt_buf-wrt_pnt, 1, outfile );
			sch_cnt++;
			eitcur = free_EIT_CONTROL( eitcur );
		}
		fwrite( "}\n", 2, 1, outfile );
	}
	free( eitsch );
	free( wrt_pnt );
}


int main(int argc, char *argv[])
{
	FILE *infile = stdin;
	FILE *outfile = stdout;
	char	*arg_onTV ;
	int   inclose = 0;
	int   outclose = 0;
	SVT_CONTROL	*svtcur ;
	SVT_CONTROL	*svtsave ;
	SECcache   secs[SECCOUNT];
	int		i , j, k ;
	int		is_logo = 0;
	int		is_xml = 0;
	SDTTdata  sdtd;
	SDTTdataLoop *loop;
	SDTTdataService *service;
	int		eit_mode = 0;
	int		sdt_mode = 0;
	int		select_sid = 0;
	int		cut_sids[64] = {0};
	char	*head = NULL;
	void	(*dumpEPG)( FILE *, SVT_CONTROL *, int );

	memset(dsmctl, 0,  sizeof(dsmctl));

	if(argc >= 4){
		if(argc >= 5 ){
			sdt_mode = 0;
			if( strcmp(argv[1], "/LOGO") == 0){
				argv[1] = argv[2];
				argv[2] = argv[3];
				argv[3] = argv[4];
				is_logo = 1;
				is_xml  = 1;
			}else{
				i = 4;
				while(1){
					if( eit_mode==0 && !strcmp( argv[i], "-pf" ) ){
						eit_mode = 1;
					}else
					if( sdt_mode==0 && !strcmp( argv[i], "-all" ) ){
						sdt_mode = 1;
					}else
					if( is_xml==0 && !strcmp( argv[i], "-xml" ) ){
						is_xml = 1;
					}else
					if( select_sid==0 && !strcmp( argv[i], "-sid" ) ){
						select_sid = atoi( argv[++i] );
					}else
					if( eit_mode==0 && select_sid==0 && !strcmp( argv[i], "-cut" ) ){
						char	*pnt;
						char	*tmp;

						k   = 0;
						pnt = argv[++i];
						if( strchr( pnt, ',' ) != NULL ){
							while( tmp=strchr( pnt, ',' ), tmp!=NULL ){
								*tmp++        = '\0';
								cut_sids[k++] = atoi( pnt );
								pnt           = tmp;
							}
							cut_sids[k] = atoi( pnt );
						}else{
							do{
								if( isdigit( (int)*pnt ) ){
									cut_sids[k++] = atoi( pnt );
									pnt           = argv[++i];
								}else{
									if( k == 0 ){
										printf( "-cut option error (Don't sid: %s)\n", pnt );
										exit( 1 );
									}else{
										i--;
										break;
									}
								}
							}while( i < argc );
						}
					}
					i++;
					if( argc <= i )
						break;
				}
			}
		}

		arg_onTV = argv[1];
		if(strcmp(argv[2], "-")) {
			infile = fopen(argv[2], "r");
			if ( !infile) {
			  printf( "tsFile not found (Can't open file: %s)\n", argv[2] );
			  exit( 1 );
			}
			inclose = 1;
		}
		else {
			infile = stdin;
		}
		if(strcmp(argv[3], "-")) {
			outfile = fopen(argv[3], "w+");
			if ( !outfile) {
			  printf( "xmlFile not found (Can't open file: %s)\n", argv[3] );
			  exit( 1 );
			}
			outclose = 1;
		}
		else {
			outfile = stdout;
		}

		dumpEPG = is_xml ? dumpXML : dumpSERIAL;
	}else{
		fprintf(stdout, "Usage : %s (/LOGO) {/BS|/CS|<id>} <tsFile> <outfile> { ( {-pf} {-sid n} ) | {-cut n1,n2} }\n", argv[0]);
		fprintf(stdout, "\n");
		fprintf(stdout, "/LOGO    ロゴ取得モード。独立して指定し、番組表の出力を行ないません。\n");
		fprintf(stdout, "         必要なTSの長さ 地上波は10分 BS/CSは20分です。\n");
		fprintf(stdout, "id       チャンネル識別子。地上波の物理チャンネルを与えます。\n");
		fprintf(stdout, "/BS      BSモード。一つのTSからBS全局のデータを読み込みます。\n");
		fprintf(stdout, "/CS      CSモード。一つのTSからCS複数局のデータを読み込みます。\n");
		fprintf(stdout, "/TIME    時刻合わせモード。TSからTOT(Time Offset Table)を読み込みます。\n");
		fprintf(stdout, "         recpt1 <任意> 10(秒以上) - | epgdump /TIME - <任意>の形で使用してください。\n");
		fprintf(stdout, "         TOTは5秒に1回しか来ないため、recpt1に与える時間をある程度長くしてください。\n");
		fprintf(stdout, "-pf      EID[pf]単独出力モード。必要なTSの長さは4秒です。\n");
		fprintf(stdout, "-sid n   BS/CS単チャンネル出力モード。nにはチャンネルsidを指定\n");
		fprintf(stdout, "-cut n   BS/CS不要チャンネル除外モード。nには不要チャンネルsidをcsv形式で指定\n");
		fprintf(stdout, "-all     全サービスを出力対象とする。\n");
		fprintf(stdout, "-xml     XMLフォーマットで出力する。\n");
/*
		fprintf(stdout, "  ontvcode   Channel identifier (ex. ****.ontvjapan.com)\n");
		fprintf(stdout, "  /BS        BS mode\n");
		fprintf(stdout, "               This mode reads the data of all BS TV stations\n");
		fprintf(stdout, "               from one TS data.\n");
		fprintf(stdout, "  /CS        CS mode\n");
		fprintf(stdout, "               This mode reads the data of two or more CS TV stations\n");
		fprintf(stdout, "               from one TS data.\n");
*/
		exit( 0 );
	}

	svttop = calloc(1, sizeof(SVT_CONTROL));

	/* 興味のあるpidを指定 */
	if ( is_logo ) {
		memset(secs, 0,  sizeof(SECcache) * SECCOUNT);
		secs[0].pid = 0x00; /* PAT  */
		secs[1].pid = 0x11; /* SDT  */
		secs[2].pid = 0x29; /* CDT  */
	}
	else {
		memset(secs, 0,  sizeof(SECcache) * SECCOUNT);
		secs[0].pid = 0x00; /* PAT  */
		secs[1].pid = 0x11; /* SDT  */
		secs[2].pid = 0x12; /* H-EIT */
		secs[3].pid = 0x23; /* SDTT */
		secs[4].pid = 0x29; /* CDT  */
#if ENABLE_1SEG
		secs[5].pid = 0x27; /* L-EIT */
#endif
#if ENABLE_ERT
		// 位置は調節すること
		secs[5].pid = 0x21; /* ERT  */
#endif
//		secs[5].pid = 0x24; /* BIT  */
		/* EIT 0x26,0x27は移動体用*/
	}

	if(strcmp(arg_onTV, "/TIME") == 0){
		printf("TSに載っている時刻データは2秒ほど早めてあるのかもしれません。\n");
		memset(secs, 0,  sizeof(SECcache) * SECCOUNT);
		secs[0].pid = 0x14; /* TOT  */

		GetSDT(infile, NULL, secs, SECCOUNT, NULL, 0, 0, 0, 0);
	}else{
		int		sdt_cnt;
		int		type = 0;

		if(strcmp(arg_onTV, "/BS") == 0){
			head = "BS";
			type = 1;
		}else if(strcmp(arg_onTV, "/CS") == 0){
			head = "CS";
			type = 2;
		}else if(strcmp(arg_onTV, "/TEST") == 0){
			memset(secs, 0,  sizeof(SECcache) * SECCOUNT);
			secs[0].pid = 0x24; /* BIT  */
			head        = "TEST";
			is_logo     = 0;
		}else{
			head = arg_onTV;	// 地上波
		}
		svt_init( svttop, cut_sids );
		GetSDT( infile, svttop, secs, SECCOUNT, head, is_logo, select_sid, sdt_mode, eit_mode );
		sdt_cnt = svt_assortment( svttop );
		if( is_xml ){
			fprintf(outfile, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
			fprintf(outfile, "<!DOCTYPE tv SYSTEM \"xmltv.dtd\">\n\n");
			fprintf(outfile, "<tv generator-info-name=\"tsEPG2xml\" generator-info-url=\"http://localhost/\">\n");
		}

		char	ServiceName[1024];
		unsigned char	Logo[8192];

		if ( is_logo ) {
			memset(Logo, '\0', sizeof(Logo));
			svtcur = svttop;
			while( svtcur->next != NULL ){
				svtcur = svtcur->next;
				for ( i = 0 ; i < 6 ; i++) {
					if (svtcur->logo_array[i].logo) {
						base64_encode(Logo, svtcur->logo_array[i].logo, svtcur->logo_array[i].logo_size);
						xmlspecialchars((char *)Logo);
						fprintf(outfile, "    <logo ts=\"%d\" on=\"%d\" sv=\"%d\" type=\"%d\">%s</logo>\n", 
							svtcur->transport_stream_id, 
							svtcur->original_network_id, 
							svtcur->service_id, 
							i, 
							Logo);
					}
				}
			}

			for ( i = 0; i < 1024; i++) {
				if ( dsmctl[i].isUsed == 0 ) break;
				parseSDTTdata(dsmctl[i].blockData, &sdtd);

				for (j = 0; j < sdtd.number_of_loop; j++) {
					loop = sdtd.loop + sizeof(SDTTdataLoop) * j;

					for ( k = 0; k < loop->number_of_services; k++) {
						service = loop->services + sizeof(SDTTdataService) * k;

						/*
						svtcur = svttop;
						while( svtcur->next != NULL ){
							svtcur = svtcur->next;
							if ( 
								svtcur->transport_stream_id == service->transport_stream_id && 
								svtcur->original_network_id == service->original_network_id && 
								svtcur->service_id == service->service_id
							) {
								clt2png(loop->data, 
									&svtcur->logo_array[sdtd.logo_type].logo, 
									&svtcur->logo_array[sdtd.logo_type].logo_size);
							}
						}
						*/

						#if 0
						printf( "SDTTdataLoop (%d:%d) %d:%d[%d:%d:%d]%d\n", 
							i, j, 
							sdtd.logo_type, 
							loop->logo_id, 
							service->transport_stream_id, 
							service->original_network_id, 
							service->service_id, 
							loop->data_size
						);
						#endif

					
						void* logo = NULL;
						int logo_size = 0;

						clt2png(loop->data, &logo, &logo_size);
						memset(Logo, '\0', sizeof(Logo));
						base64_encode(Logo, logo, logo_size);
						xmlspecialchars((char *)Logo);

						fprintf(outfile, "  <logo ts=\"%d\" on=\"%d\" sv=\"%d\" type=\"%d\" dlid=\"%d\">%s</logo>\n", 
							service->transport_stream_id, 
							service->original_network_id, 
							service->service_id, 
							sdtd.logo_type, 
							loop->logo_id, 
							Logo);
					}

				}
			}
		}
		if( is_xml ){
			svtcur = svttop;
			while( svtcur->next != NULL ){
				svtcur = svtcur->next;
				memset(ServiceName, '\0', sizeof(ServiceName));
				strcpy(ServiceName, svtcur->servicename);
				xmlspecialchars(ServiceName);

				fprintf(outfile, "  <channel id=\"%s\">\n", svtcur->ontv);
				fprintf(outfile, "    <display-name lang=\"ja_JP\">%s</display-name>\n", ServiceName);
#if ENABLE_ERT
				fprintf(outfile, "    <id ts=\"%d\" on=\"%d\" sv=\"%d\" st=\"%d\" slot=\"%d\"/>\n",
					svtcur->transport_stream_id, svtcur->original_network_id, svtcur->service_id, svtcur->service_type, svtcur->slot);
#else
				fprintf(outfile, "    <id ts=\"%d\" on=\"%d\" sv=\"%d\" st=\"%d\"/>\n",
					svtcur->transport_stream_id, svtcur->original_network_id, svtcur->service_id, svtcur->service_type);
#endif
				fprintf(outfile, "  </channel>\n");
			}
		}else{
			fprintf( outfile, "a:%i:{", sdt_cnt );
			sdt_cnt = 0;
			svtcur  = svttop;
			while( svtcur->next != NULL ){
				svtcur = svtcur->next;
				unsigned int	node = type==0 ? 0 : ( svtcur->transport_stream_id & 0x01f0U ) >> 4;
				unsigned int	slot = type==0 ? 0 : svtcur->transport_stream_id & 0x0007U;

				if( type==1 && ( svtcur->transport_stream_id==0x40f1U || svtcur->transport_stream_id==0x40f2U ) )
					slot--;
				fprintf( outfile,
					"i:%i;a:8:{"
					"s:2:\"id\";s:%i:\"%s\";"
					"s:12:\"display-name\";s:%i:\"%s\";"
					"s:2:\"ts\";i:%i;"
					"s:2:\"on\";i:%i;"
					"s:2:\"sv\";i:%i;"
					"s:2:\"st\";i:%i;"
					"s:4:\"node\";i:%i;"
					"s:4:\"slot\";i:%i;}",
					sdt_cnt,
					strlen(svtcur->ontv), svtcur->ontv,
					strlen(svtcur->servicename), svtcur->servicename,
					svtcur->transport_stream_id, svtcur->original_network_id, svtcur->service_id, svtcur->service_type,
					node, slot
				);
				sdt_cnt++;
			}
			fwrite( "}\n", 2, 1, outfile );
		}
		if( !is_logo ){
			pf_regulation( svttop->next );
			svtcur = svttop;
			while( svtcur->next != NULL ){
				svtcur = svtcur->next;
				if( svtcur->import_stat == 2 ){
#if KATAUNA
					shobo_( svtcur->servicename, head, svtcur->service_id, eit_mode );
#endif
					(*dumpEPG)( outfile, svtcur, eit_mode );
#if KATAUNA
					free_syobo();
#endif
				}
			}
		}
		if( is_xml )
			fprintf(outfile, "</tv>\n");
	}
	if(inclose) {
		fclose(infile);
	}

	if(outclose) {
		fclose(outfile);
	}

	svtcur = svttop ;	//先頭
	while(svtcur != NULL){
		svtsave = svtcur->next ;
		free(svtcur);
		svtcur = svtsave ;
	}

	exit( 0 );
}
