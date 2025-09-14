#ifndef BIT_H
#define BIT_H 1

#include "util.h"
#include "ts_ctl.h"

typedef struct _BIThead {
	unsigned char table_id;
	int  section_syntax_indicator;
	int  reserved_future_use1;
	int  reserved1;
	int  section_length;
	int  original_network_id;
	int  reserved2;
	int  version_number;
	int  current_next_indicator;
	int  section_number;
	int  last_section_number;
	int  reserved_future_use2;
	int  broadcast_view_propriety;
	int  first_descriptors_length;
	// first_descriptors skipped
} BIThead;

typedef struct _BITloop {
	int  broadcaster_id;
	int  reserved_future_use;
	int  broadcaster_descriptors_length;
} BITloop;

typedef struct _BITdesc {
	int  descriptor_tag;
	int  descriptor_length;
	int  parameter_version;
	int  update_time;
} BITdesc;

typedef struct _BITtable {
	int  table_id;
	int  table_description_length;
	int  table_cycle;
} BITtable;

#ifdef __cplusplus
extern "C"{
#endif /* __cplusplus */

	int parseBIThead(unsigned char *data, BIThead *head);
	int parseBITdesc(unsigned char *data, BITdesc *desc);
	void dumpBIT(unsigned char *ptr);

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif

