PREFIX		= /usr/local
TARGETS	= epgdump
OBJ_TARGETS	= epgdump.o aribstr.o eit.o ts.o util.o psi.o sdt.o cdt.o sdtt.o dsmcc.o tot.o bit.o clt2png.o
HEDDERDEPEND	=           aribstr.h eit.h ts.h util.h psi.h sdt.h cdt.h sdtt.h dsmcc.h tot.h bit.h clt2png.h
#OBJ_TARGETS	= epgdump.o aribstr.o eit.o ts.o util.o psi.o sdt.o cdt.o sdtt.o dsmcc.o tot.o bit.o clt2png.o ert.o
#HEDDERDEPEND	=           aribstr.h eit.h ts.h util.h psi.h sdt.h cdt.h sdtt.h dsmcc.h tot.h bit.h clt2png.h ert.h

LANG		= C
CC		= gcc

#CFLAGS		= -std=c99 -D_POSIX_C_SOURCE=200112 -O2 -Wall -g
#CFLAGS		= -std=c99 -D_POSIX_C_SOURCE=200112 -O0 -Wall -ggdb
CFLAGS		= -std=c99 -D_POSIX_C_SOURCE=200112 -O2 -Wall -ggdb -mfpmath=sse -msse3 -march=native
#CFLAGS		= -std=c99 -D_POSIX_C_SOURCE=200112 -O2 -Wall -ggdb -lefence

CXX		= g++
CXXFLAGS	= -O2 -Wall -ggdb -lstdc++

LIBS		= 

.c.o:			${CC} ${CFLAGS} -c $<

.cpp.o:			$(CXX) $(CXXFLAGS) -o $@ -c $^

all:			${TARGETS}

${TARGETS}:		${OBJ_TARGETS}
			${CC} ${CFLAGS} ${OBJ_TARGETS} -o $@ ${LDFLAGS} ${LIBS}

${OBJ_TARGETS}:	${HEDDERDEPEND}

clean:
			rm -f core ${TARGETS} *.o

install:		${TARGETS}
			install -m755 ${TARGETS} ${PREFIX}/bin

uninstall:		${TARGETS}
			rm ${PREFIX}/bin/${TARGETS}

