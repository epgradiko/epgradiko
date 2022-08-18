#ifndef CDT_H
#define CDT_H 1
/*
#include <stdio.h>
#include <stdlib.h>

#include "sdt.h"
#include "util.h"
#include "ts_ctl.h"
*/
typedef struct _CDThead {
	unsigned char table_id;
	int  section_syntax_indicator;
	int  reserved_future_use1;
	int  reserved1;
	int  section_length;
	int  download_data_id;
	int  reserved2;
	int  version_number;
	int  current_next_indicator;
	int  section_number;
	int  last_section_number;
	int  original_network_id;
	int  reserved_future_use2;
	int  data_type;
	int  descriptors_loop_length;
} CDThead;

typedef struct _CDTdesc {
	unsigned char  descriptor_tag;
	int  descriptor_length;
} CDTdesc;

typedef struct _CDTdata {
	int	logo_type;
	int	reserved_future_use1;
	int	logo_id;
	int	reserved_future_use2;
	int	logo_version;
	int	data_size;
	void	*data;
} CDTdata;

#ifdef __cplusplus
extern "C"{
#endif /* __cplusplus */
/*
	int parseCDThead(unsigned char *data, CDThead *h);
	int parseCDTdesc(unsigned char *data, CDTdesc *desc);
	void dumpCDT(unsigned char *ptr, SVT_CONTROL *svttop );
*/
#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif
