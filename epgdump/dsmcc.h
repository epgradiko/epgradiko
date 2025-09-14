#ifndef DSMCC_H
#define DSMCC_H 1

#include <stdio.h>
#include <stdlib.h>

#include "sdt.h"
#include "util.h"
#include "ts_ctl.h"

typedef struct _DSMCChead {
	int table_id;
	int section_syntax_indicator;
	int complement_indicator;
	int reserved1;
	int section_length;
	int table_id_extension;
	int reserved2;
	int version_number;
	int current_next_indicator;
	int section_number;
	int last_section_number;
} DSMCChead;
/*
typedef struct _DSMCCmsghead {
	int  protocolDiscriminator;
	int  dsmccType;
	int  messageId;
	int  transaction_id;
	int  reserved;
	int  adaptationLength;
	int  messageLength;
	void *dsmccAdaptationHeader;
} DSMCCmsghead;
*/
typedef struct _DSMCCbodyDIIModule {
	int  moduleId;
	int  moduleSize;
	int  moduleVersion;
	int  moduleInfoLength;
	unsigned char *moduleInfo;

	int	descriptor_tag;
	int	descriptor_length;
	char	*Type;
	char	*Name;
	char	*Info;
} DSMCCbodyDIIModule;

typedef struct _DSMCCbodyDII {
	int  protocolDiscriminator;
	int  dsmccType;
	int  messageId;
	int  transaction_id;
	int  reserved;
	int  adaptationLength;
	int  messageLength;
	void *dsmccAdaptationHeader;

	int  downloadId;
	int  blockSize;
	int  windowSize;
	int  ackPeriod;
	int  tCDownloadWindow;
	int  tCDownloadScenario;
	int  compatibilityDescriptor;
	int  numberOfModules;
	void *modules;
	int  privateDataLength;
	void *privateData;
} DSMCCbodyDII;

typedef struct _DSMCCbodyDDB {
	int  protocolDiscriminator;
	int  dsmccType;
	int  messageId;
	unsigned int  downloadId;
	int  reserved1;
	int  adaptationLength;
	int  messageLength;
	void *dsmccAdaptationHeader;

	int  moduleId;
	int  moduleVersion;
	int  reserved2;
	int  blockNumber;
	void *blockData;
} DSMCCbodyDDB;

#ifdef __cplusplus
extern "C"{
#endif /* __cplusplus */

	int parseDSMCChead(unsigned char *data, DSMCChead *h);
	int parseDSMCCbodyDDB(unsigned char *data, DSMCCbodyDDB *dsmbddb);
	void dumpDSMCC(unsigned char *ptr, int *downloadDataId, DSM_CONTROL *dsmctl);

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif

