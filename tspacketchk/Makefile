
CC = cc
#CFLAGS = -g
CFLAGS = -O2 
LDFLAGS = 
OBJS = tspacketchk.o tsselect.o main.o
PROGRAM = tspacketchk
DEST = /usr/local/bin

$(PROGRAM): $(OBJS)
	$(CC) $(LDFLAGS) -o $@ $(OBJS)

.c.o:
	$(CC) $(CFLAGS) -c $<

all: $(PROGRAM) 

clean:
	rm $(OBJS) $(PROGRAM) 

install:$(PROGRAM1)
	install -s $(PROGRAM) $(DEST)

tspacketchk.o: def.h
tsselect.o:  def.h
main.o:   def.h
