alter table pp_project_file add hot_count int(10) not null default 0 after status;
alter table pp_project_file add not_count int(10) not null default 0 after hot_count;
alter table pp_project_file add hot_count_anon int(10) not null default 0 after not_count;
alter table pp_project_file add not_count_anon int(10) not null default 0 after hot_count_anon;
alter table pp_project_file add hot_count_pro int(10) not null default 0 after not_count_anon;
alter table pp_project_file add not_count_pro int(10) not null default 0 after hot_count_pro;