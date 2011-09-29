-- schema for MOOS DB
-- author: Ian Katz
-- last edited: 09/09

create table if not exists mission (
   mission_id smallint unsigned not null auto_increment,
   date date,
   time time,
   vehicle_name varchar(31),
   label varchar(100),
   location varchar(63),
   origin_latitude double,
   origin_longitude double,
   notes text,

   primary key (mission_id)
) engine=InnoDB;

create table if not exists app_data (
   mission_id smallint unsigned not null,
   elapsed_time double not null,
   varname varchar(100) not null,
   app varchar(100),
   value double,

   primary key (mission_id, elapsed_time, varname, app),
   foreign key (mission_id) references mission(mission_id) on delete cascade
) engine=InnoDB;

create table if not exists app_messages (
   mission_id smallint unsigned not null,
   elapsed_time double not null,
   varname varchar(100) not null,
   app varchar(100),
   message varchar(255),

   primary key (mission_id, elapsed_time, varname, app),
   foreign key (mission_id) references mission(mission_id) on delete cascade
) engine=InnoDB;

create table if not exists text_files (
   mission_id smallint unsigned not null,
   file_name varchar(200) not null,
   file text not null,

   primary key (mission_id, file_name),
   foreign key (mission_id) references mission(mission_id) on delete cascade
) engine=InnoDB;

