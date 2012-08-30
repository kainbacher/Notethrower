alter table pp_project_file add hot_count int(10) not null default 0 after status;
alter table pp_project_file add not_count int(10) not null default 0 after hot_count;