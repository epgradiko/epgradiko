

#define _LARGEFILE64_SOURCE
#if __WORDSIZE != 64
#define _FILE_OFFSET_BITS    64
#endif


#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <stdint.h>

#include <sys/io.h>
#include <fcntl.h>
#include <sys/stat.h>
#include <sys/types.h>
#include <unistd.h>
#include <time.h>

#include <stdio.h>
#include <getopt.h>
#include <stdbool.h>
#include <errno.h> 

//#define INT64  uint64_t
#define INT64  unsigned long long

typedef struct {                /* read buff */
  int           fd;
  unsigned char *buf;
  int           bufsize;
  int           unit_size;
  unsigned char *curr;
  unsigned char *tail;
  bool          eof;
  long long     total;
} ReadBuf;


typedef struct {

  int             pid;
  int             last_continuity_counter;

  unsigned long     total;
  unsigned long     error;
  unsigned long     drop;
  unsigned long     scrambling;
  unsigned char     last_packet[188];
  int               duplicate_count;

} TS_STATUS;


typedef struct {
  int           sync;
  int           transport_error_indicator;
  int           payload_unit_start_indicator;
  int           transport_priority;
  int           pid;
  int           transport_scrambling_control;
  int           adaptation_field_control;
  int           continuity_counter;
} TS_HEADER;

typedef struct {

  int           adaptation_field_length;
	
  int           discontinuity_counter;
  int           random_access_indicator;
  int           elementary_stream_priority_indicator;
  int           pcr_flag;
  int           opcr_flag;
  int           splicing_point_flag;
  int           transport_private_data_flag;
  int           adaptation_field_extension_flag;

  INT64         program_clock_reference;
  INT64         original_program_clock_reference;

  int           splice_countdown;

  int           transport_private_data_length;
	
  int           adaptation_field_extension_length;
  int           ltw_flag;
  int           piecewise_rate_flag;
  int           seamless_splice_flag;
  int           ltw_valid_flag;
  int           ltw_offset;
  int           piecewise_rate;
  int           splice_type;
  INT64         dts_next_au;
	
} ADAPTATION_FIELD;

typedef struct {                /* verbose data */
  int       lineC;
  bool      snip;
} VERBOSE_DATA;


#define BUF_SIZE 1024 * 32

extern char *version;
extern bool  opt_p;
extern bool  opt_P;
extern bool  opt_S;
extern bool  opt_d;
extern int   opt_s ;
extern int   opt_l ;


void show_usage();
int select_unit_size(unsigned char *head, unsigned char *tail);
unsigned char *resync(unsigned char *head, unsigned char *tail, int unit_size);
void extract_ts_header(TS_HEADER *dst, unsigned char *packet);
void extract_adaptation_field(ADAPTATION_FIELD *dst, unsigned char *data);


INT64   getPCR(TS_HEADER *hdr, ADAPTATION_FIELD *adapt );
bool    packetEmpty( ReadBuf *rb );
bool    skip( INT64 pcr  );
char    *pcr2str( INT64 pcr);
int     read_buf( ReadBuf *rb );
unsigned char *resyncRB(ReadBuf *rb );
int     packetchk(const char *path);
bool    verbose( VERBOSE_DATA *vd, INT64 pcr, int pcc, char *type, int pid );
INT64   durationCalc( INT64 start, INT64 end );
