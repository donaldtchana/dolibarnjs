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
-- along with this program.  If not, see https://www.gnu.org/licenses/.


CREATE TABLE llx_manufacturingproduction_journal (
  rowid int(11) NOT NULL AUTO_INCREMENT,
  entity int(11) DEFAULT '1',
  label varchar(255) NOT NULL,
  fk_costcenter int(11) NOT NULL,
  fk_project int(11) DEFAULT NULL,
  type varchar(1) NOT NULL,
  origin int(2) NOT NULL,
  fk_linked_id int(11) DEFAULT NULL,
  date datetime NOT NULL,
  qty float(8,2) NOT NULL,
  amount decimal(24,8) NOT NULL,
  PRIMARY KEY (rowid)
) ENGINE=InnoDB AUTO_INCREMENT=168 DEFAULT CHARSET=latin1