#ifndef ERT_H
#define ERT_H 1


typedef struct _ERThead {
	unsigned char table_id;
	int  section_syntax_indicator;
	int  reserved_future_use1;
	int  reserved1;
	int  section_length;
	int  event_relation_id;
	int  reserved2;
	int  version_number;
	int  current_next_indicator;
	int  section_number;
	int  last_section_number;
	int  information_provider_id;
	int  relation_type;
	int  reserved_future_use2;
} ERThead;

typedef struct _ERTbody {
	int  node_id;
	int  collection_mode;
	int  reserved_future_use1;
	int  parent_node_id;
	int  reference_number;
	int  reserved_future_use2;
	int  descriptors_loop_length;
} ERTbody;


#endif

