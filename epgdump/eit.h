#ifndef EIT_H
#define EIT_H 1

/*
#include <stdio.h>
#include <stdlib.h>

#include "util.h"
#include "ts_ctl.h"
*/

typedef struct _EIThead {
	int table_id;
	int section_syntax_indicator;
	int reserved_future_use;
	int reserved1;
	int section_length;
	int service_id;
	int reserved2;
	int version_number;
	int current_next_indicator;
	int section_number;
	int last_section_number;
	int transport_stream_id;
	int original_network_id;
	int segment_last_section_number;
	int last_table_id;
} EIThead;

typedef struct _EITbody {
	int		event_id;
	int		running_status;
	int		free_CA_mode;
	int		descriptors_loop_length;
	/* 以下は解析結果保存用 */
	int		yy;
	int		mm;
	int		dd;
	int		hh;
	int		hm;
	int		ss;
	int		duration;
	int		event_status;
} EITbody;

typedef struct _SEVTdesc {
	int  descriptor_tag;
	int  descriptor_length;
	char ISO_639_language_code[3];
	int  event_name_length;
	char event_name[MAXSECLEN];
	int  text_length;
	char text[MAXSECLEN];
} SEVTdesc;

typedef struct _ContentDesc {
	int descriptor_tag;
	int descriptor_length;
	char content[MAXSECLEN];
} ContentDesc;

typedef struct _SeriesDesc {
	int descriptor_tag;
	int descriptor_length;
	int series_id;
	int repeat_label;
	int program_pattern;
	int expire_date_valid_flag;
	int expire_date;
	int episode_number;
	int last_episode_number;
	char series_name_char[MAXSECLEN];
} SeriesDesc;

typedef struct _ComponentDesc {
	int descriptor_tag;
	int descriptor_length;
	int reserved_future_use;
	int stream_content;
	int component_type;
	int component_tag;
	char ISO_639_language_code[3];
	char text_char[MAXSECLEN];
} ComponentDesc;

typedef struct _AudioComponentDesc {
	int descriptor_tag;
	int descriptor_length;
	int reserved_future_use_1;
	int stream_content;
	int component_type;
	int component_tag;
	int stream_type;
	int simulcast_group_tag;
	int ES_multi_lingual_flag;
	int main_component_flag;
	int quality_indicator;
	int sampling_rate;
	int reserved_future_use_2;
	char ISO_639_language_code_1[3];
	char ISO_639_language_code_2[3];
	char text_char[MAXSECLEN];
} AudioComponentDesc;

typedef struct _EEVTDhead {
	int  descriptor_tag;
	int  descriptor_length;
	int  descriptor_number;
	int  last_descriptor_number;
	char ISO_639_language_code[3];
	int  length_of_items;
} EEVTDhead;

typedef struct _EEVTDitem {
	int  item_description_length;
	char item_description[MAXSECLEN];
	int  item_length;
	char item[MAXSECLEN];
	/* 退避用 */
	int  descriptor_number;
} EEVTDitem;

typedef struct _EEVTDtail {
	int  text_length;
	char text[MAXSECLEN];
} EEVTDtail;

#ifdef __cplusplus
extern "C"{
#endif /* __cplusplus */

/*	int parseEIThead(unsigned char *data, EIThead *h);
	int parseEITbody(unsigned char *data, EITbody *b, int);
	int parseSEVTdesc(unsigned char *data, SEVTdesc *desc) ;

	int parseContentDesc(unsigned char *data, ContentDesc *desc);
	int parseSeriesDesc(unsigned char *data, SeriesDesc *desc);

	int parseEEVTDhead(unsigned char *data, EEVTDhead *desc) ;
	int parseEEVTDitem(unsigned char *data, EEVTDitem *desc) ;
	int parseEEVTDtail(unsigned char *data, EEVTDtail *desc) ;
	EIT_CONTROL	*searcheit(EIT_CONTROL *top, int servid, int eventid);
	void dumpEIT(unsigned char *data, SVT_CONTROL *svttop, int mode );
*/

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif
