-- Copyright (C) 2022 SuperAdmin <marcello.gribaudo@opigi.com>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu

CREATE TABLE llx_manufacturingproduction_costcenter (
  rowid int(11) NOT NULL AUTO_INCREMENT,
  entity int(11) unsigned DEFAULT '1',
  master varchar(2) DEFAULT NULL,
  detail varchar(2) DEFAULT NULL,
  sub_detail varchar(4) DEFAULT NULL,
  moveable tinyint(1) DEFAULT NULL,
  type varchar(1) DEFAULT NULL,
  label varchar(255) DEFAULT NULL,
  value double(24,8) DEFAULT NULL,
  PRIMARY KEY (rowid),
  UNIQUE KEY idx_anufacturingproduction_costcenter_code (master,detail,sub_detail)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=latin1