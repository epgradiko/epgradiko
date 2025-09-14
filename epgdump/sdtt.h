#ifndef SDTT_H
#define SDTT_H 1

#include <stdio.h>
#include <stdlib.h>

#include "sdt.h"
#include "util.h"
#include "ts_ctl.h"

typedef struct _SDTThead {
	unsigned char table_id;
	int  section_syntax_indicator;
	int  reserved_future_use1;
	int  reserved1;
	int  section_length;
	int  maker_id;
	int  model_id;
//	int  table_id_ext;
	int  reserved2;
	int  version_number;
	int  current_next_indicator;
	int  section_number;
	int  last_section_number;
	int  transport_stream_id;
	int  original_network_id;
	int  service_id;
	int  num_of_contents;
} SDTThead;

typedef struct _SDTTcont {
	int  group;
	int  target_version;
	int  new_version;
	int  download_level;
	int  version_indicator;
	int  content_description_length;
	int  reserved1;
	int  schedule_description_length;
	int  schedule_timeshift_information;
} SDTTcont;

typedef struct _SDTTdescModule {
	int	module_id;
	int	module_size;
	int	module_info_length;
	void	*module_info_byte;
	int	descriptor_tag;
	int	descriptor_length;
	char	*Type;
	char	*Name;
	char	*Info;
} SDTTdescModule;

typedef struct _SDTTdesc {
	int  descriptor_tag;
	int  descriptor_length;
	int  reboot;
	int  add_on;
	int  compatibility_flag;
	int  compatibility_length;
	int  module_info_flag;
	int  text_info_flag;
	int  reserved1;
	int  component_size;
	unsigned int  download_id;
	int  time_out_value_DII;
	int  leak_rate;
	int  reserved2;
	int  component_tag;
	int  num_of_modules;
	void  *modules;
	int  private_data_length;
	void  *private_data_byte;
	int  ISO_639_language_code;
	int  text_length;
	//void  *text_char;
	char text_char[MAXSECLEN];
} SDTTdesc;

typedef struct _SDTTdataService {
	int  original_network_id;
	int  transport_stream_id;
	int  service_id;
} SDTTdataService;

typedef struct _SDTTdataLoop {
	int	reserved_future_use1;
	int	logo_id;
	int	number_of_services;
	SDTTdataService	*services;
	int	data_size;
	void	*data;
} SDTTdataLoop;

typedef struct _SDTTdataBC {
	int	logo_type;
	int	number_of_loop;
	SDTTdataLoop	*loop;
} SDTTdata;

#ifdef __cplusplus
extern "C"{
#endif /* __cplusplus */

	int parseSDTThead(unsigned char *data, SDTThead *h);
	int parseSDTTdesc(unsigned char *data, SDTTdesc *sdts);
	int parseSDTTdata(unsigned char *data, SDTTdata *sdtd);
	void dumpSDTT(unsigned char *ptr, SVT_CONTROL *svttop);

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif

