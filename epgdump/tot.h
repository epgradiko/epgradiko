#ifndef TOT_H
#define TOT_H 1
/*
#include <stdio.h>
#include <stdlib.h>

#include "util.h"
#include "ts_ctl.h"
*/
typedef struct _TOThead {
	unsigned char table_id;
	int  section_syntax_indicator;
	int  reserved_future_use1;
	int  reserved1;
	int  section_length;
	unsigned char  JST_time[5];
	int  reserved2;
	int  descriptors_loop_length;
} TOThead;

typedef struct _TOTdesc {
	unsigned char  descriptor_tag;
	int  descriptor_length;
} TOTdesc;

#ifdef __cplusplus
extern "C"{
#endif /* __cplusplus */

	int parseTOThead(unsigned char *data, TOThead *toth);
	int parseTOTdesc(unsigned char *data, TOTdesc *totd);
	void dumpTOT(unsigned char *ptr);

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif
