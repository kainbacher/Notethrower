alter table pp_user add column is_proudloudr tinyint(1) not null default 0 after is_pro;
alter table pp_user add column is_editor tinyint(1) not null default 0 after is_proudloudr;
alter table pp_user add column is_admin tinyint(1) not null default 0 after is_editor;
alter table pp_user add column wants_newsletter tinyint(1) not null default 0 after is_admin;