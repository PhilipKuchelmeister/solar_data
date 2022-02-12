FROM debian:latest

WORKDIR /usr/src

RUN apt-get update; \
    apt-get -y install wget make cmake unzip;\
    wget https://github.com/PhilipKuchelmeister/solar_data/blob/main/yasdi-1.8.1build9-src.zip; \
    unzip yasdi-1.8.1build9-src.zip; \
    mkdir /usr/src/projects/generic-cmake/build-gcc

WORKDIR /usr/src/projects/generic-cmake/build-gcc

RUN cmake ..; \
    make; \
    make install; \
    sudo ldconfig; \
    apt-get clean; \
    rm -rf /usr/src/*;

RUN cd; \
    vi yasdi.conf
      [DriverModules]
      Driver0=libyasdi_drv_serial.so
      
      [COM1]
      Device=/dev/ttyUSB0
      Media=RS485
      Baudrate=1200
      Protocol=SMANet

WORKDIR /

ENV LD_LIBRARY_PATH /usr/local/lib
