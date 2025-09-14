// -*- tab-width:4 -*-

#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "sdtt.h"
#include "ts_ctl.h"
#include "clt2png.h"

// STD-B21 p130 (144)
int parseSDTThead(unsigned char *data, SDTThead *h) {
	int boff = 0;

	memset(h, 0, sizeof(SDTThead));

	h->table_id = getBit(data, &boff, 8);
	h->section_syntax_indicator = getBit(data, &boff, 1);
	h->reserved_future_use1 = getBit(data, &boff, 1);
	h->reserved1 = getBit(data, &boff, 2);
	h->section_length = getBit(data, &boff, 12);
	h->maker_id = getBit(data, &boff, 8);
	h->model_id = getBit(data, &boff, 8);
	// boff -= 16;
	// h->table_id_ext = getBit(data, &boff, 16);
	h->reserved2 = getBit(data, &boff, 2);
	h->version_number = getBit(data, &boff, 5);
	h->current_next_indicator = getBit(data, &boff, 1);
	h->section_number = getBit(data, &boff, 8);
	h->last_section_number = getBit(data, &boff, 8);
	h->transport_stream_id = getBit(data, &boff, 16);
	h->original_network_id = getBit(data, &boff, 16);
	h->service_id = getBit(data, &boff, 16);
	h->num_of_contents = getBit(data, &boff, 8);

	return 15;
}

// STD-B21 p130 (144)
int parseSDTTcont(unsigned char *data, SDTTcont *sdtc) {
	int boff = 0;

	memset(sdtc, 0, sizeof(SDTTcont));
	sdtc->group = getBit(data, &boff, 4);
	sdtc->target_version = getBit(data, &boff, 12);
	sdtc->new_version = getBit(data, &boff, 12);
	sdtc->download_level = getBit(data, &boff, 2);
	sdtc->version_indicator = getBit(data, &boff, 2);
	sdtc->content_description_length = getBit(data, &boff, 12);
	sdtc->reserved1 = getBit(data, &boff, 4);
	sdtc->schedule_description_length = getBit(data, &boff, 12);
	sdtc->schedule_timeshift_information = getBit(data, &boff, 4);	
	/*
	for (i=0; i<sdtc->schedule_description_length / 8; i++) {
		sdtc->start_time = getBit(data, &boff, 40);
		sdtc->duration = getBit(data, &boff, 24);
	}
	*/

	return sdtc->schedule_description_length + 8;
	//return sdtc->content_description_length + 8;
}

// STD-B21 p132 (146)
int parseSDTTdesc(unsigned char *data, SDTTdesc *sdts) {
// 0xC9(201)
	int boff = 0;
	int i = 0;
	SDTTdescModule *module;

	memset(sdts, 0, sizeof(SDTTdesc));
	sdts->descriptor_tag = getBit(data, &boff, 8);
	sdts->descriptor_length = getBit(data, &boff, 8);
	sdts->reboot = getBit(data, &boff, 1);
	sdts->add_on = getBit(data, &boff, 1);
	sdts->compatibility_flag = getBit(data, &boff, 1);
	sdts->module_info_flag = getBit(data, &boff, 1);
	sdts->text_info_flag = getBit(data, &boff, 1);
	sdts->reserved1 = getBit(data, &boff, 3);
	sdts->component_size = getBit(data, &boff, 32);
	sdts->download_id = getBit(data, &boff, 32);
	sdts->time_out_value_DII = getBit(data, &boff, 32);
	sdts->leak_rate = getBit(data, &boff, 22);
	sdts->reserved2 = getBit(data, &boff,2);
	sdts->component_tag = getBit(data, &boff, 8);

	if ( sdts->compatibility_flag == 1) {
		sdts->compatibility_length = getBit(data, &boff, 16);
		boff += sdts->compatibility_length * 8;
	}
	if ( sdts->module_info_flag == 1) {
		sdts->num_of_modules = getBit(data, &boff, 16);

		if ( sdts->num_of_modules > 0 ) 
			sdts->modules = calloc(1, sizeof(SDTTdescModule) * ( sdts->num_of_modules + 1 ));

		for (i=0; i<sdts->num_of_modules; i++) {
			module = sdts->modules + sizeof(SDTTdescModule) * i;// &sdts->modules[i];
			module->module_id = getBit(data, &boff, 16);
			module->module_size = getBit(data, &boff, 32);
			module->module_info_length = getBit(data, &boff, 8);
			if ( *(data + boff / 8) == 0x01 ) {
				/* Type 記述子 モジュールの型(MIME 形式等) */
				module->descriptor_tag = getBit(data, &boff, 8);
				module->descriptor_length = getBit(data, &boff, 8);
				module->Type = allocCopy(data, &boff, module->descriptor_length);
				module->Type[module->descriptor_length] = '\0';
				// +1 byte for null-terminated
			}
			else if ( *(data + boff / 8) == 0x02 ) {
				/* Name 記述子 モジュール名(ファイル名) */
				module->descriptor_tag = getBit(data, &boff, 8);
				module->descriptor_length = getBit(data, &boff, 8);
				module->Name = allocCopy(data, &boff, module->descriptor_length);
				module->Name[module->descriptor_length] = '\0';
			}
			else if ( *(data + boff / 8) == 0x03 ) {
				/* Info 記述子 モジュール情報(文字型) */
				module->descriptor_tag = getBit(data, &boff, 8);
				module->descriptor_length = getBit(data, &boff, 8);
				boff += 24; // ISO_639_language_code
				module->Info = allocCopy(data, &boff, module->descriptor_length - 3);
				module->Info[module->descriptor_length] = '\0';
			}
			else {
				module->module_info_byte = allocCopy(data, &boff, module->module_info_length);
			}
		}
	}
	sdts->private_data_length = getBit(data, &boff, 8);
	sdts->private_data_byte = allocCopy(data, &boff, sdts->private_data_length);
	if ( sdts->text_info_flag == 1) {
		sdts->ISO_639_language_code = getBit(data, &boff, 24);
		sdts->text_length = getBit(data, &boff, 8);
		getStr(sdts->text_char, data, &boff, sdts->text_length);
		//sdts->text_char = allocCopy(data, &boff, sdts->text_length);
	}

	return boff / 8;
}

// STD-B21 p193 (209)
int parseSDTTdata(unsigned char *data, SDTTdata *sdtd) {
	int boff = 0, i, j;
	SDTTdataLoop *loop;
	SDTTdataService *service;

	memset(sdtd, 0, sizeof(SDTTdata));

	sdtd->logo_type = getBit(data, &boff, 8);
	sdtd->number_of_loop = getBit(data, &boff, 16);
	sdtd->loop = calloc(1, ( sizeof(SDTTdataLoop) + 5000 ) * sdtd->number_of_loop);

	for (i=0; i<sdtd->number_of_loop; i++) {
		loop = sdtd->loop + sizeof(SDTTdataLoop) * i;

		loop->reserved_future_use1 = getBit(data, &boff, 7);
		loop->logo_id = getBit(data, &boff, 9);
		loop->number_of_services = getBit(data, &boff, 8);
		loop->services = calloc(1, ( sizeof(SDTTdataService) + 5000 )* loop->number_of_services);

		for (j=0; j<loop->number_of_services; j++) {
			service = loop->services + sizeof(SDTTdataService) * j;
			service->original_network_id = getBit(data, &boff, 16);
			service->transport_stream_id = getBit(data, &boff, 16);
			service->service_id = getBit(data, &boff, 16);
		}
		loop->data_size = getBit(data, &boff, 16);
		loop->data = allocCopy(data, &boff, loop->data_size);
	}

	return boff / 8;
}

void dumpSDTT(unsigned char *ptr, SVT_CONTROL *svttop )
{
	SDTThead  sdth;
	SDTTcont  sdtc;
	SDTTdesc  sdts;
//	SDTTdata  sdtd;
	SDTTdescModule *module;

	int len = 0;
	int loop_len = 0;
	int desc_len = 0;
	int i;

	/* SDTT */
	len = parseSDTThead(ptr, &sdth);
	ptr += len;
	loop_len = sdth.section_length - (len - 3 + 4); // 3は共通ヘッダ長 4はCRC

	/*
	printf("SDTT=(%d:%d:%d:%d:%d:%d:%d:%d:%d:%d)\n",
		sdth.table_id, sdth.section_number, sdth.version_number, 
		sdth.maker_id, sdth.model_id, 
		sdth.transport_stream_id, sdth.original_network_id, 
		sdth.service_id, sdth.num_of_contents, sdth.section_length);
	*/

	if ( sdth.maker_id == 0xff && sdth.model_id == 0xfe )
		printf("BS FOUND\n");

	if ( sdth.maker_id == 0xff && sdth.model_id == 0xfc )
		printf("BS/広帯域 CS FOUND\n");

	if ( ! ( sdth.maker_id == 0xff && ( sdth.model_id == 0xfc || sdth.model_id == 0xfe ) ) )
		return;

	while(loop_len > 0) {
		/*
		logo_type
		0x00 24x48 864  SD4:3 スモール 
		0x01 24x36 648  SD16:9 スモール
		0x02 27x48 972  HD スモール 
		0x03 36x72 1296 SD4:3 ラージ 
		0x04 36x54 972  SD16:9 ラージ 
		0x05 36x64 1152 HD ラージ 
		*/

		for (i=0; i<sdth.num_of_contents; i++) {
			len = parseSDTTcont(ptr, &sdtc);
			ptr += len;
			loop_len -= len;

			desc_len = sdtc.content_description_length - sdtc.schedule_description_length;
			loop_len -= desc_len;

			while(desc_len > 0) {
				if ( *ptr != 0xC9 ) {
					len = parseOTHERdesc(ptr);
					ptr += len;
					desc_len -= len;
					continue;
				}

				len = parseSDTTdesc(ptr, &sdts);
				ptr += len;
				desc_len -= len;
#if 0
				printf("SDTTdesc %d:%d:%d:%d:%d:%d:%d:%d:%d:%d:%d:%d:%s\n",
					sdts.descriptor_tag, sdts.descriptor_length, 
					sdts.reboot, sdts.add_on, 
					sdts.compatibility_flag, sdts.module_info_flag, sdts.text_info_flag, 
					sdts.component_size, sdts.download_id, sdts.time_out_value_DII, 
					sdts.leak_rate, sdts.component_tag, 
					sdts.text_info_flag ? sdts.text_char : "");
#endif
				for (i=0; i<sdts.num_of_modules; i++) {
					module = sdts.modules + sizeof(SDTTdescModule) * i;
					if ( module->descriptor_tag == 0x01 ) {
						printf("sdts.Type %s id:%d\n", module->Type, sdts.download_id);
					}
					else if ( module->descriptor_tag == 0x02 ) {
						printf("sdts.Name %s id:%d\n", module->Name, sdts.download_id);

						if ( strstr( module->Name, "CS_LOGO" ) ) {
							//svttop->next->llogo_download_data_id = sdts.download_id;
						}
						else if ( strstr( module->Name, "LOGO" ) ) {
							svttop->next->logo_download_data_id = sdts.download_id;
						}
					}
					else if ( module->descriptor_tag == 0x03 ) {
						printf("sdts.Info %s id:%d\n", module->Info, sdts.download_id);
					}
					else {
						printf("MIB: %s\n", (char *)module->module_info_byte);
					}
				}
/*
				len = parseSDTTdata(ptr, &sdtd);
				ptr += len;
				desc_len -= len;
*/
#if 0
			printf("SDTT=(%d:%d:%d:%d:%d:%dbyte:desc%dbyte)%d,%d,%d,%d\n",
				cdth.table_id, cdth.download_data_id, cdth.version_number, 
				cdth.original_network_id, cdth.data_type, 
				cdth.section_length, cdth.descriptors_loop_length, 

				sdtdte.logo_type, sdtdte.logo_id, sdtdte.logo_version,
				sdtdte.data_size);
#endif
			}
		}
	}
	return;
}

