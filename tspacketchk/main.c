/*
 *  MPEG TS packet checker 
 */

#include "def.h"

char *version  = "1.0.0";
bool  opt_p     = false ;
bool  opt_P     = false ;
bool  opt_S     = false ;
bool  opt_d     = false ;
int   opt_s     = 0 ;
int   opt_l     = 16 ;


int main(int argc, char* argv[])
{
    int c;
    int i;
    int longindex = 0;
    const char* optstring = "pPSdl:s:h";
    const struct option longopts[] = {
        {  "limit",  required_argument,     0, 'l' },
        {  "skip",   required_argument,     0, 's' },
        {  "progress",     no_argument,     0, 'p' },
        {  "silent",       no_argument,     0, 'S' },
        {  "PCR",          no_argument,     0, 'P' },
        {  "debug",        no_argument,     0, 'd' },
        {  "help",         no_argument,     0, 'h' },
        {         0,                 0,     0,  0  },
    };
    opterr = 0; // disable error log

    while ((c=getopt_long(argc, argv, optstring, longopts, &longindex)) != -1) {
     switch (c) {
     case 'd':
       opt_d = true ; break ;
     case 'p':
       opt_p = true ; break ;
     case 'S':
       opt_S = true ; break ;
     case 'P':
       opt_P = true ; break ;
     case 'l':
       opt_l = (int)strtol( optarg, NULL, 0); break;
     case 's':
       opt_s = (int)strtol( optarg, NULL, 0); break;
     case 'h':
       show_usage();
     default :
       show_usage();
     }
    }
    if ( argc == optind )  show_usage();

    c = 0;

    for ( i=optind; i< argc ; i++ ) {
      if ( i > optind ) printf("\n\n");
      if ( opt_S ) {
        int	str_len;
	str_len = strlen( argv[i] );
	while( str_len  && ( *( argv[i] + str_len - 1) != '/') ) str_len--;
        printf("< %s >\n\n", argv[i] + str_len);
      } else {
        printf("<<< %s >>>\n\n", argv[i]);
      }
      c =+ packetchk( (const char *)argv[i] );
    }
    exit(c);
}


void show_usage()
{
  printf( "tspacketchk - MPEG-2 TS packet checker ver. %s\n", version);
  printf( "usage: tspacketchk [オプション]... TSファイル... \n");
  printf( "\n");
  printf( "  -l, --limit n   詳細表示の行数を n行にする。デフォルトは 16\n");
  printf( "  -s, --skip n    開始直後の n秒はエラーを無視する\n");
  printf( "  -p, --progress  進捗状況の表示\n");
  printf( "  -S, --silent    サマリ情報を標準エラーにコンパクト出力\n");
  printf( "  -P, --PCR       PCR Wrap-around check の追加\n");
  printf( "  -h, --help      この使い方を表示して終了する\n");
  printf( "\n");

  if ( opt_d == true ) {
    INT64         int64;
    uint64_t      uint64_t;
    int           inta;
    long          longa;
    long long     longl;

    printf( "int = %d, long = %d, long long %d, uint64_t = %d, INT64  = %d\n",
            (int)sizeof(inta),
            (int)sizeof(longa),
            (int)sizeof(longl),
            (int)sizeof(int64),
            (int)sizeof(uint64_t)
    );
  }
  
  
  exit(0);
}
