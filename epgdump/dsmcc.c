// -*- tab-width:4 -*-

#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "dsmcc.h"
#include "ts_ctl.h"
#include "clt2png.h"

// STD-B21 p130 (144)
// a_90-with-att.pdf p24 (29/99)
int parseDSMCChead(unsigned char *data, DSMCChead *dsmh) {
	int boff = 0;

	memset(dsmh, 0, sizeof(DSMCChead));

	dsmh->table_id = getBit(data, &boff, 8);
	dsmh->section_syntax_indicator = getBit(data, &boff, 1);
	dsmh->complement_indicator = getBit(data, &boff,1);
	dsmh->reserved1 = getBit(data, &boff, 2);
	dsmh->section_length = getBit(data, &boff, 12);
	dsmh->table_id_extension = getBit(data, &boff, 16);
	dsmh->reserved2 = getBit(data, &boff, 2);
	dsmh->version_number = getBit(data, &boff, 5);
	dsmh->current_next_indicator = getBit(data, &boff, 1);
	dsmh->section_number = getBit(data, &boff, 8);
	dsmh->last_section_number = getBit(data, &boff, 8);

	return 8;
}

// a_90-with-att.pdf p29 (34/99)
// 2-STD-B24v5_4-3p3.pdf p16 (30/125)
int parseDSMCCbodyDII(unsigned char *data, DSMCCbodyDII *dsmbdii) {
	int boff = 0, i;
	DSMCCbodyDIIModule *module;

	memset(dsmbdii, 0, sizeof(DSMCCbodyDII));

	// header
	dsmbdii->protocolDiscriminator = getBit(data, &boff, 8);
	dsmbdii->dsmccType = getBit(data, &boff, 8);
	dsmbdii->messageId = getBit(data, &boff, 16);
	dsmbdii->transaction_id = getBit(data, &boff, 32);
	dsmbdii->reserved = getBit(data, &boff, 8);
	dsmbdii->adaptationLength = getBit(data, &boff, 8);
	dsmbdii->messageLength = getBit(data, &boff, 16);
	if ( dsmbdii->adaptationLength > 0 ) {
		dsmbdii->dsmccAdaptationHeader = allocCopy(data, &boff, dsmbdii->adaptationLength);
	}

	// body
	dsmbdii->downloadId = getBit(data, &boff, 32);
	dsmbdii->blockSize = getBit(data, &boff, 16);
	dsmbdii->windowSize = getBit(data, &boff, 8);
	dsmbdii->ackPeriod = getBit(data, &boff, 8);
	dsmbdii->tCDownloadWindow = getBit(data, &boff, 32);
	dsmbdii->tCDownloadScenario = getBit(data, &boff, 32);
	dsmbdii->compatibilityDescriptor = getBit(data, &boff, 16);
/*
	see http://www.atsc.org/cms/standards/a_90-with-att.pdf

	compatibilityDescriptorLength	16
	descriptorCount	16
	for(i=0;i<descriptorCount;i++) {
		descriptorType		8
		descriptorLength	8
		specifierType		8
		specifierData		24
		model				16
		version				16
		subDescriptorCount	8
		for(j=0;j<subDescriptorCount;j++) {
			subDescriptor() {
				subDescriptorType	8
				subDescriptorLength	8
				for(k=0;k<subDescriptorLength;k++) {
					additionalInformation	8
		}
	}
*/
	boff += dsmbdii->compatibilityDescriptor * 8;
	dsmbdii->numberOfModules = getBit(data, &boff, 16);

	if ( dsmbdii->numberOfModules > 0 ) {
		dsmbdii->modules = calloc(1, sizeof(DSMCCbodyDIIModule) * dsmbdii->numberOfModules + 1000);

		for (i=0; i<dsmbdii->numberOfModules; i++) {
			module = dsmbdii->modules + sizeof(DSMCCbodyDIIModule) * i;
			module->moduleId = getBit(data, &boff, 16);
			module->moduleSize = getBit(data, &boff, 32);
			module->moduleVersion = getBit(data, &boff, 8);
			module->moduleInfoLength = getBit(data, &boff, 8);

			if ( *(data + boff / 8) == 0x01 ) {
				/* Type 記述子 モジュールの型(MIME 形式等) */
				module->descriptor_tag = getBit(data, &boff, 8);
				module->descriptor_length = getBit(data, &boff, 8);
				module->Type = allocCopy(data, &boff, module->descriptor_length + 1);
				module->Type[module->descriptor_length] = '\0';
				// +1 byte for null-terminated
			}
			else if ( *(data + boff / 8) == 0x02 ) {
				/* Name 記述子 モジュール名(ファイル名) */
				module->descriptor_tag = getBit(data, &boff, 8);
				module->descriptor_length = getBit(data, &boff, 8);
				module->Name = allocCopy(data, &boff, module->descriptor_length + 1);
				module->Name[module->descriptor_length] = '\0';
			}
			else if ( *(data + boff / 8) == 0x03 ) {
				/* Info 記述子 モジュール情報(文字型) */
				module->descriptor_tag = getBit(data, &boff, 8);
				module->descriptor_length = getBit(data, &boff, 8);
				boff += 24; // ISO_639_language_code
				module->Info = allocCopy(data, &boff, module->descriptor_length - 3 + 1);
				module->Info[module->descriptor_length] = '\0';
			}
			else {
				module->moduleInfo = allocCopy(data, &boff, module->moduleInfoLength);
			}
		}
	}

	dsmbdii->privateDataLength = getBit(data, &boff, 8);
	dsmbdii->privateData = allocCopy(data, &boff, dsmbdii->privateDataLength);

	return boff / 8;
}

// a_90-with-att.pdf p35 (40/99)
int parseDSMCCbodyDDB(unsigned char *data, DSMCCbodyDDB *dsmbddb) {
	int boff = 0;

	memset(dsmbddb, 0, sizeof(DSMCCbodyDDB));

	// header
	dsmbddb->protocolDiscriminator = getBit(data, &boff, 8);
	dsmbddb->dsmccType = getBit(data, &boff, 8);
	dsmbddb->messageId = getBit(data, &boff, 16);
	dsmbddb->downloadId = getBit(data, &boff, 32);
	dsmbddb->reserved1 = getBit(data, &boff, 8);
	dsmbddb->adaptationLength = getBit(data, &boff, 8);
	dsmbddb->messageLength = getBit(data, &boff, 16);
	if ( dsmbddb->adaptationLength > 0 ) {
		dsmbddb->dsmccAdaptationHeader = allocCopy(data, &boff, dsmbddb->adaptationLength);
	}

	// body
	dsmbddb->moduleId = getBit(data, &boff, 16);
	dsmbddb->moduleVersion = getBit(data, &boff, 8);
	dsmbddb->reserved2 = getBit(data, &boff, 8);
	dsmbddb->blockNumber = getBit(data, &boff, 16);
	dsmbddb->blockData = allocCopy(data, &boff, dsmbddb->messageLength);

	return boff / 8;
}

void dumpDSMCC(unsigned char *ptr, int * downloadDataId, DSM_CONTROL *dsmctl)
{
	DSMCChead  dsmh;
	DSMCCbodyDII  dsmbdii;
	DSMCCbodyDDB  dsmbddb;
	DSMCCbodyDIIModule *module;

	int len = 0;
	int i = 0;

	/* DSMCC */
	len = parseDSMCChead(ptr, &dsmh);
	ptr += len;
	//loop_len = dsmh.section_length - (len - 3 + 4); // 3は共通ヘッダ長 4はCRC
/*
	printf("DSMCChead=(%d:%d:%d:%d)\n",
		dsmh.table_id, dsmh.section_length, 
		dsmh.table_id_extension, dsmh.section_number);
*/
	if ( dsmh.table_id == 0x3B ) {
		len = parseDSMCCbodyDII(ptr, &dsmbdii);
#if 0
		printf("DSMCCbDII=(%d:%d:%d:%d:%d:%d) (%d:%d:%d:%d)\n",
			dsmbdii.protocolDiscriminator, dsmbdii.dsmccType, 
			dsmbdii.messageId, dsmbdii.transaction_id, 
			dsmbdii.adaptationLength, dsmbdii.messageLength, 

			dsmbdii.downloadId , dsmbdii.blockSize , dsmbdii.compatibilityDescriptor , 
			dsmbdii.numberOfModules);
#endif
		for (i=0; i<dsmbdii.numberOfModules; i++) {
			module = dsmbdii.modules + sizeof(DSMCCbodyDIIModule) * i;
			// 0x01 Type 記述子
			// 0x02 Name 記述子
			// 0x03 Info 記述子
			if ( module->descriptor_tag == 0x01 ) {
				//printf("1 %s\n", module->Type);
			}
			else if ( module->descriptor_tag == 0x02 ) {
				//printf("2 %s\n", module->Name);
				// is_bs_cs == 1 && 
				if ( !strncmp( module->Name, "LOGO", 4 ) ) {
					//printf("%s(%d) : id = %d\n", module->Name, is_bs_cs, dsmbdii.downloadId);
					*downloadDataId = dsmbdii.downloadId;
				}
				// なぜかBSにCSのロゴも載ってるため
				// is_bs_cs == 2 && 
				else if ( !strncmp( module->Name, "CS_LOGO", 7 ) ) {
					//printf("%s(%d) : id = %d\n", module->Name, is_bs_cs, dsmbdii.downloadId);
					*downloadDataId = dsmbdii.downloadId;
				}
			}
			else if ( module->descriptor_tag == 0x03 ) {
				//printf("3 %s\n", module->Info);
			}
		}
	}
	else if ( dsmh.table_id == 0x3C ) {
		len = parseDSMCCbodyDDB(ptr, &dsmbddb);
		if ( *downloadDataId == dsmbddb.downloadId ) {
//		if ( 33882368 == dsmbddb.downloadId ) {
//		{
#if 0
			printf("DSMCCbDDB=(%d:%d:%d:%d:%d:%d) (%d:%d:%d)\n",
				dsmbddb.protocolDiscriminator, dsmbddb.dsmccType, 
				dsmbddb.messageId, dsmbddb.downloadId, 
				dsmbddb.adaptationLength, dsmbddb.messageLength, 

				dsmbddb.moduleId , dsmbddb.moduleVersion , dsmbddb.blockNumber);
#endif

			for (i = 0; i < 1024; i++) {
				if ( dsmctl[i].isUsed == 0 ) {
					// リストの終端まで来たので
					//printf("moduleId=%d as dsmctl[%d]\n", dsmbddb.moduleId, i);
					dsmctl[i].moduleId = dsmbddb.moduleId;
					dsmctl[i].lastBlockNumber = -1;
					dsmctl[i].isUsed = 1;
				}
				if ( dsmctl[i].moduleId == dsmbddb.moduleId ) {
					if ( dsmctl[i].lastBlockNumber + 1 == dsmbddb.blockNumber ) {
						dsmbddb.messageLength -= 6; // length of moduleId, moduleVersion, reserved, blockNumber
						//printf("moduleId=%d as dsmctl[%d] size %d += %d\n", dsmbddb.moduleId, i, dsmctl[i].blockSize, dsmbddb.messageLength);
						dsmctl[i].blockData = realloc( dsmctl[i].blockData, dsmctl[i].blockSize + dsmbddb.messageLength );
						memcpy( dsmctl[i].blockData + dsmctl[i].blockSize, dsmbddb.blockData, dsmbddb.messageLength );
						dsmctl[i].blockSize += dsmbddb.messageLength;
						dsmctl[i].lastBlockNumber++;
					}
					else {
						//printf("ignoring %d(max %d)\n", dsmbddb.blockNumber, dsmctl[i].lastBlockNumber);
					}
					break;
				}
			}
		}
	}

	return;
}

