#ifndef PSI_H
#define PSI_H 1

#include <stdio.h>
#include <stdlib.h>

#include "ts.h"
#include "util.h"

typedef struct _PAThead {
	unsigned char table_id;
	int  section_syntax_indicator;
	int  zero;
	int  reserved1;
	int  section_length;
	int  transport_stream_id;
	int  reserved2;
	int  version_number;
	int  current_next_indicator;
	int  section_number;
	int  last_section_number;
} PAThead;

typedef struct _PATbody {
	int  program_number;
	int  reserved;
	int  network_PID;
	int  program_map_PID;
} PATbody;

typedef struct _PMThead {
	int  table_id;
	int  section_syntax_indicator;
	int  zero;
	int  reserved1;
	int  section_length;
	int  program_number;
	int  reserved2;
	int  version_number;
	int  current_next_indicator;
	int  section_number;
	int  last_section_number;
	int  reserved3;
	int  PCR_PID;
	int  reserved4;
	int  program_info_length;
} PMThead;

typedef struct _PMTbody {
	int  stream_type;
	int  reserved1;
	int  elementary_PID;
	int  reserved2;
	int  ES_info_length;
} PMTbody;

#ifdef __cplusplus
extern "C"{
#endif /* __cplusplus */

	int parsePAThead(unsigned char *data, PAThead *path);
	int parsePATbody(unsigned char *data, PATbody *patb);
	void dumpPAT(unsigned char *ptr, SECcache *secs, int count, int *pmtpids);
	void dumpPMT(unsigned char *ptr, SECcache *secs, int count, int *dsmccpids);

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif

