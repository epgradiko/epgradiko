// -*- tab-width:4 -*-

#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "psi.h"

// 2-STD-B10v4_6.pdf p178 (190/399)
int parsePAThead(unsigned char *data, PAThead *path) {
	int boff = 0;

	memset(path, 0, sizeof(PAThead));

	path->table_id = getBit(data, &boff, 8);
	path->section_syntax_indicator = getBit(data, &boff, 1);
	path->zero = getBit(data, &boff, 1);
	path->reserved1 = getBit(data, &boff, 2);
	path->section_length = getBit(data, &boff, 12);
	path->transport_stream_id = getBit(data, &boff, 16);
	path->reserved2 = getBit(data, &boff, 2);
	path->version_number = getBit(data, &boff, 5);
	path->current_next_indicator = getBit(data, &boff, 1);
	path->section_number = getBit(data, &boff, 8);
	path->last_section_number = getBit(data, &boff, 8);

	return 8;
}

int parsePATbody(unsigned char *data, PATbody *patb) {
	int boff = 0;

	memset(patb, 0, sizeof(PATbody));

	patb->program_number = getBit(data, &boff, 16);
	patb->reserved = getBit(data, &boff, 3);
	if ( patb->program_number == 0 ) {
		patb->network_PID = getBit(data, &boff, 13);
	}
	else {
		patb->program_map_PID = getBit(data, &boff, 13);
	}

	return 4;
}

void dumpPAT(unsigned char *ptr, SECcache *secs, int count, int *pmtpids)
{
	int len = 0;
	int loop_len = 0;
	int i;

	PAThead path;
	PATbody patb;

	/* PAT */
	len = parsePAThead(ptr, &path);
	ptr += len;

	// printf("PAT=()\n");

	loop_len = path.section_length;
	while ( loop_len > 0 ) {
		len = parsePATbody(ptr, &patb);
		ptr += len;
		loop_len -= len;

		if ( patb.program_number != 0 ) {
			for ( i = 1; i < count; i++ ) {
				if ( secs[i].pid == patb.program_map_PID ) {
					break;
				}
				if ( secs[i].pid == 0 ) {
					// printf("PAT: Adding PID(0x%04x) to secs[%d]\n", patb.program_map_PID, i);
					secs[i].pid = patb.program_map_PID;
					break;
				}
			}
			for ( i = 0; i < count; i++ ) {
				if ( pmtpids[i] == patb.program_map_PID ) {
					break;
				}
				if ( pmtpids[i] == 0 ) {
					// printf("PAT: Adding PID(0x%04x) to pmtpids[%d]\n", patb.program_map_PID, i);
					pmtpids[i] = patb.program_map_PID;
					break;
				}
			}
		}
	}

	return;
}

// 2-STD-B10v4_6.pdf p180 (192/399(
int parsePMThead(unsigned char *data, PMThead *pmth) {
	int boff = 0;

	memset(pmth, 0, sizeof(PMThead));

	pmth->table_id = getBit(data, &boff, 8);
	pmth->section_syntax_indicator = getBit(data, &boff, 1);
	pmth->zero = getBit(data, &boff, 1);
	pmth->reserved1 = getBit(data, &boff, 2);
	pmth->section_length = getBit(data, &boff, 12);
	pmth->program_number = getBit(data, &boff, 16);
	pmth->reserved2 = getBit(data, &boff, 2);
	pmth->version_number = getBit(data, &boff, 5);
	pmth->current_next_indicator = getBit(data, &boff, 1);
	pmth->section_number = getBit(data, &boff, 8);
	pmth->last_section_number = getBit(data, &boff, 8);
	pmth->reserved3 = getBit(data, &boff, 3);
	pmth->PCR_PID = getBit(data, &boff, 13);
	pmth->reserved4 = getBit(data, &boff, 4);
	pmth->program_info_length = getBit(data, &boff, 12);

	return 12;
}

int parsePMTbody(unsigned char *data, PMTbody *pmtb) {
	int boff = 0;

	memset(pmtb, 0, sizeof(PMTbody));

	pmtb->stream_type = getBit(data, &boff, 8);
	pmtb->reserved1 = getBit(data, &boff, 3);
	pmtb->elementary_PID = getBit(data, &boff, 13);
	pmtb->reserved2 = getBit(data, &boff, 4);
	pmtb->ES_info_length = getBit(data, &boff, 12);

	return 5;
}

void dumpPMT(unsigned char *ptr, SECcache *secs, int count, int *dsmccpids)
{
	int len = 0;
	int loop_len = 0;
	int desc_len = 0;
	int i;

	PMThead pmth;
	PMTbody pmtb;

	/* PMT */
	len = parsePMThead(ptr, &pmth);
	ptr += len;
/*
	printf("PMT=(%d:%d:%d:%d:%d:%d:%d:%d)\n", 
		pmth.table_id, pmth.section_length , 
		pmth.program_number , pmth.version_number , 
		pmth.section_number , pmth.last_section_number , 
		pmth.PCR_PID , pmth.program_info_length);
*/
	loop_len = pmth.program_info_length;
	while ( loop_len > 0 ) {
		len = parseOTHERdesc(ptr);
		ptr += len;
		loop_len -= len;
	}

	loop_len = pmth.section_length - pmth.program_info_length - 13;// 9はヘッダ長 4はCRC
	while ( loop_len > 0 ) {
		len = parsePMTbody(ptr, &pmtb);
		ptr += len;
		loop_len -= len;
/*
		printf("PMTb=(0x%x:%d:%d)\n", 
			pmtb.stream_type , pmtb.elementary_PID , pmtb.ES_info_length);
*/
		// 2-STD-B24v5_4-3p3.pdf p11 (25/125)
		if ( pmtb.stream_type == 0x0B || pmtb.stream_type == 0x0D ) {
			for ( i = 1; i < count; i++ ) {
				if ( secs[i].pid == pmtb.elementary_PID ) {
					break;
				}
				if ( secs[i].pid == 0 ) {
					//printf("PMT: Adding PID(0x%04x) to secs[%d]\n", pmtb.elementary_PID, i);
					secs[i].pid = pmtb.elementary_PID;
					break;
				}
			}
			for ( i = 0; i < count; i++ ) {
				if ( dsmccpids[i] == pmtb.elementary_PID ) {
					break;
				}
				if ( dsmccpids[i] == 0 ) {
					//printf("PMT: Adding PID(0x%04x) to dsmccpids[%d]\n", pmtb.elementary_PID, i);
					dsmccpids[i] = pmtb.elementary_PID;
					break;
				}
			}
		}

		desc_len = pmtb.ES_info_length;
		loop_len -= desc_len;

		while ( desc_len > 0 ) {
			len = parseOTHERdesc(ptr);
			ptr += len;
			desc_len -= len;
		}
	}

	return;
}

