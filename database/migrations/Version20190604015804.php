<?php namespace Database\Migrations;
/**
 * Copyright 2017 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;
/**
 * Class Version20190604015804
 * @package Database\Migrations
 */
final class Version20190604015804 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $initial_state = <<<SQL

create table if not exists oauth2_api_scope_group
(
	id bigint unsigned auto_increment
		primary key,
	name varchar(512) not null,
	description text not null,
	active tinyint(1) default '1' not null,
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null
)
collate=utf8_unicode_ci
;

create table if not exists oauth2_resource_server
(
	id bigint unsigned auto_increment
		primary key,
	friendly_name varchar(255) not null,
	host varchar(512) not null,
	active tinyint(1) default '1' not null,
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	ips text not null,
	constraint oauth2_resource_server_friendly_name_unique
		unique (friendly_name)
)
collate=utf8_unicode_ci
;

create table if not exists oauth2_api
(
	id bigint unsigned auto_increment
		primary key,
	name varchar(255) not null,
	logo varchar(255) null,
	description text null,
	active tinyint(1) default '1' not null,
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	resource_server_id bigint unsigned not null,
	constraint oauth2_api_name_resource_server_id_unique
		unique (name, resource_server_id),
	constraint oauth2_api_resource_server_id_foreign
		foreign key (resource_server_id) references oauth2_resource_server (id)
			on delete cascade
)
collate=utf8_unicode_ci
;

create index oauth2_api_resource_server_id_index
	on oauth2_api (resource_server_id)
;

create table if not exists oauth2_api_endpoint
(
	id bigint unsigned auto_increment
		primary key,
	active tinyint(1) default '1' not null,
	description text null,
	name varchar(255) not null,
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	route text not null,
	http_method enum('GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'TRACE', 'CONNECT', 'OPTIONS', 'PATCH') not null,
	api_id bigint unsigned not null,
	allow_cors tinyint(1) default '1' not null,
	rate_limit bigint unsigned null,
	constraint oauth2_api_endpoint_name_http_method_api_id_unique
		unique (name, http_method, api_id),
	constraint oauth2_api_endpoint_api_id_foreign
		foreign key (api_id) references oauth2_api (id)
			on delete cascade
)
collate=utf8_unicode_ci
;

create index oauth2_api_endpoint_api_id_index
	on oauth2_api_endpoint (api_id)
;

create table if not exists oauth2_api_scope
(
	id bigint unsigned auto_increment
		primary key,
	name varchar(512) not null,
	short_description varchar(512) not null,
	description text not null,
	active tinyint(1) default '1' not null,
	`default` tinyint(1) default '0' not null,
	system tinyint(1) default '0' not null,
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	api_id bigint unsigned null,
	assigned_by_groups tinyint(1) default '0' not null,
	constraint oauth2_api_scope_api_id_foreign
		foreign key (api_id) references oauth2_api (id)
			on delete cascade
)
collate=utf8_unicode_ci
;

create table if not exists oauth2_api_endpoint_api_scope
(
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	api_endpoint_id bigint unsigned not null,
	scope_id bigint unsigned not null,
	constraint oauth2_api_endpoint_api_scope_api_endpoint_id_foreign
		foreign key (api_endpoint_id) references oauth2_api_endpoint (id)
			on delete cascade,
	constraint oauth2_api_endpoint_api_scope_scope_id_foreign
		foreign key (scope_id) references oauth2_api_scope (id)
			on delete cascade
)
collate=utf8_unicode_ci
;

create index oauth2_api_endpoint_api_scope_api_endpoint_id_index
	on oauth2_api_endpoint_api_scope (api_endpoint_id)
;

create index oauth2_api_endpoint_api_scope_scope_id_index
	on oauth2_api_endpoint_api_scope (scope_id)
;

create index oauth2_api_scope_api_id_index
	on oauth2_api_scope (api_id)
;

create table if not exists oauth2_api_scope_group_scope
(
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	group_id bigint unsigned not null,
	scope_id bigint unsigned not null,
	constraint oauth2_api_scope_group_scope_group_id_foreign
		foreign key (group_id) references oauth2_api_scope_group (id)
			on delete cascade,
	constraint oauth2_api_scope_group_scope_scope_id_foreign
		foreign key (scope_id) references oauth2_api_scope (id)
			on delete cascade
)
collate=utf8_unicode_ci
;

create index oauth2_api_scope_group_scope_group_id_index
	on oauth2_api_scope_group_scope (group_id)
;

create index oauth2_api_scope_group_scope_scope_id_index
	on oauth2_api_scope_group_scope (scope_id)
;

create table if not exists openid_associations
(
	id bigint unsigned auto_increment
		primary key,
	identifier varchar(255) not null,
	mac_function varchar(255) not null,
	secret blob not null,
	realm varchar(1024) null,
	type smallint(6) not null,
	lifetime int unsigned not null,
	issued datetime not null
)
collate=utf8_unicode_ci
;

create table if not exists openid_users
(
	id bigint unsigned auto_increment
		primary key,
	identifier varchar(255) not null,
	active tinyint(1) default '1' not null,
	`lock` tinyint(1) default '0' not null,
	public_profile_show_photo tinyint(1) default '0' not null,
	public_profile_show_fullname tinyint(1) default '0' not null,
	public_profile_show_email tinyint(1) default '0' not null,
	last_login_date datetime not null,
	login_failed_attempt int default '0' not null,
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	remember_token varchar(100) null,
	external_identifier bigint unsigned null,
	constraint openid_users_external_identifier_unique
		unique (external_identifier),
	constraint openid_users_identifier_unique
		unique (identifier)
)
collate=utf8_unicode_ci
;

create table if not exists banned_ips
(
	id bigint unsigned auto_increment
		primary key,
	ip varchar(1024) not null,
	hits bigint unsigned default '1' not null,
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	exception_type varchar(1024) not null,
	user_id bigint unsigned null,
	constraint banned_ips_user_id_foreign
		foreign key (user_id) references openid_users (id)
			on delete cascade
)
collate=utf8_unicode_ci
;

create index banned_ips_user_id_index
	on banned_ips (user_id)
;

create table if not exists oauth2_api_scope_group_users
(
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	group_id bigint unsigned not null,
	user_id bigint unsigned not null,
	constraint oauth2_api_scope_group_users_group_id_foreign
		foreign key (group_id) references oauth2_api_scope_group (id)
			on delete cascade,
	constraint oauth2_api_scope_group_users_user_id_foreign
		foreign key (user_id) references openid_users (id)
			on delete cascade
)
collate=utf8_unicode_ci
;

create index oauth2_api_scope_group_users_group_id_index
	on oauth2_api_scope_group_users (group_id)
;

create index oauth2_api_scope_group_users_user_id_index
	on oauth2_api_scope_group_users (user_id)
;

create table if not exists oauth2_client
(
	id bigint unsigned auto_increment
		primary key,
	app_name varchar(255) not null,
	app_description text not null,
	app_logo varchar(255) null,
	client_id varchar(255) not null,
	client_secret varchar(255) null,
	client_type enum('PUBLIC', 'CONFIDENTIAL') default 'CONFIDENTIAL' null,
	active tinyint(1) default '1' not null,
	locked tinyint(1) default '0' not null,
	user_id bigint unsigned null,
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	max_auth_codes_issuance_qty int default '0' not null,
	max_auth_codes_issuance_basis smallint(6) not null,
	max_access_token_issuance_qty int default '0' not null,
	max_access_token_issuance_basis smallint(6) not null,
	max_refresh_token_issuance_qty int default '0' not null,
	max_refresh_token_issuance_basis smallint(6) not null,
	use_refresh_token tinyint(1) default '0' not null,
	rotate_refresh_token tinyint(1) default '0' not null,
	pkce_enabled tinyint(1) default '0' not null,
	resource_server_id bigint unsigned null,
	website text null,
	application_type enum('WEB_APPLICATION', 'JS_CLIENT', 'SERVICE', 'NATIVE') default 'WEB_APPLICATION' null,
	client_secret_expires_at datetime null,
	contacts text null,
	allowed_origins text null,
	redirect_uris text null,
	logo_uri varchar(255) null,
	tos_uri varchar(255) null,
	post_logout_redirect_uris text null,
	logout_uri text null,
	logout_session_required tinyint(1) default '0' not null,
	logout_use_iframe tinyint(1) default '0' not null,
	policy_uri varchar(255) null,
	jwks_uri varchar(255) null,
	default_max_age int default '-1' not null,
	require_auth_time tinyint(1) default '0' not null,
	token_endpoint_auth_method enum('client_secret_basic', 'client_secret_post', 'client_secret_jwt', 'private_key_jwt', 'none') default 'none' not null,
	token_endpoint_auth_signing_alg enum('HS256', 'HS384', 'HS512', 'RS256', 'RS384', 'RS512', 'PS256', 'PS384', 'PS512', 'none') default 'none' not null,
	subject_type enum('public', 'pairwise') default 'public' not null,
	userinfo_signed_response_alg enum('HS256', 'HS384', 'HS512', 'RS256', 'RS384', 'RS512', 'PS256', 'PS384', 'PS512', 'none') default 'none' not null,
	userinfo_encrypted_response_alg enum('RSA1_5', 'RSA-OAEP', 'RSA-OAEP-256', 'dir', 'none') default 'none' not null,
	userinfo_encrypted_response_enc enum('A128CBC-HS256', 'A192CBC-HS384', 'A256CBC-HS512', 'none') default 'none' not null,
	id_token_signed_response_alg enum('HS256', 'HS384', 'HS512', 'RS256', 'RS384', 'RS512', 'PS256', 'PS384', 'PS512', 'none') default 'none' not null,
	id_token_encrypted_response_alg enum('RSA1_5', 'RSA-OAEP', 'RSA-OAEP-256', 'dir', 'none') default 'none' not null,
	id_token_encrypted_response_enc enum('A128CBC-HS256', 'A192CBC-HS384', 'A256CBC-HS512', 'none') default 'none' not null,
	edited_by_id bigint unsigned null,
	constraint oauth2_client_client_id_unique
		unique (client_id),
	constraint oauth2_client_edited_by_id_foreign
		foreign key (edited_by_id) references openid_users (id),
	constraint oauth2_client_resource_server_id_foreign
		foreign key (resource_server_id) references oauth2_resource_server (id),
	constraint oauth2_client_user_id_foreign
		foreign key (user_id) references openid_users (id)
)
collate=utf8_unicode_ci
;

create table if not exists oauth2_asymmetric_keys
(
	id bigint unsigned auto_increment
		primary key,
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	pem_content text not null,
	kid varchar(255) not null,
	active tinyint(1) default '1' not null,
	`usage` enum('sig', 'enc') default 'sig' not null,
	class_name enum('ClientPublicKey', 'ServerPrivateKey') default 'ClientPublicKey' not null,
	type enum('RSA', 'EC') default 'RSA' not null,
	last_use datetime null,
	password text null,
	valid_from datetime not null,
	valid_to datetime not null,
	alg enum('RS256', 'RS384', 'RS512', 'PS256', 'PS384', 'PS512', 'RSA1_5', 'RSA-OAEP', 'RSA-OAEP-256', 'dir', 'none') default 'none' not null,
	oauth2_client_id bigint unsigned null,
	constraint oauth2_assymetric_keys_oauth2_client_id_foreign
		foreign key (oauth2_client_id) references oauth2_client (id)
			on delete cascade
)
collate=utf8_unicode_ci
;

create index oauth2_assymetric_keys_oauth2_client_id_index
	on oauth2_asymmetric_keys (oauth2_client_id)
;

create index oauth2_client_edited_by_id_index
	on oauth2_client (edited_by_id)
;

create index oauth2_client_resource_server_id_index
	on oauth2_client (resource_server_id)
;

create index oauth2_client_user_id_index
	on oauth2_client (user_id)
;

create table if not exists oauth2_client_admin_users
(
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	oauth2_client_id bigint unsigned not null,
	user_id bigint unsigned not null,
	constraint oauth2_client_admin_users_oauth2_client_id_foreign
		foreign key (oauth2_client_id) references oauth2_client (id)
			on delete cascade,
	constraint oauth2_client_admin_users_user_id_foreign
		foreign key (user_id) references openid_users (id)
			on delete cascade
)
collate=utf8_unicode_ci
;

create index oauth2_client_admin_users_oauth2_client_id_index
	on oauth2_client_admin_users (oauth2_client_id)
;

create index oauth2_client_admin_users_user_id_index
	on oauth2_client_admin_users (user_id)
;

create table if not exists oauth2_client_allowed_origin
(
	id bigint unsigned auto_increment
		primary key,
	allowed_origin text not null,
	client_id bigint unsigned not null,
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	constraint oauth2_client_allowed_origin_client_id_foreign
		foreign key (client_id) references oauth2_client (id)
			on delete cascade
)
collate=utf8_unicode_ci
;

create index oauth2_client_allowed_origin_client_id_index
	on oauth2_client_allowed_origin (client_id)
;

create table if not exists oauth2_client_api_scope
(
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	client_id bigint unsigned not null,
	scope_id bigint unsigned not null,
	constraint oauth2_client_api_scope_client_id_foreign
		foreign key (client_id) references oauth2_client (id)
			on delete cascade,
	constraint oauth2_client_api_scope_scope_id_foreign
		foreign key (scope_id) references oauth2_api_scope (id)
			on delete cascade
)
collate=utf8_unicode_ci
;

create index oauth2_client_api_scope_client_id_index
	on oauth2_client_api_scope (client_id)
;

create index oauth2_client_api_scope_scope_id_index
	on oauth2_client_api_scope (scope_id)
;

create table if not exists oauth2_client_authorized_uri
(
	id bigint unsigned auto_increment
		primary key,
	uri varchar(255) not null,
	client_id bigint unsigned not null,
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	constraint oauth2_client_authorized_uri_client_id_foreign
		foreign key (client_id) references oauth2_client (id)
			on delete cascade
)
collate=utf8_unicode_ci
;

create index oauth2_client_authorized_uri_client_id_index
	on oauth2_client_authorized_uri (client_id)
;

create table if not exists oauth2_exception_trail
(
	id bigint unsigned auto_increment
		primary key,
	from_ip varchar(254) not null,
	exception_type varchar(1024) not null,
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	client_id bigint unsigned null,
	constraint oauth2_exception_trail_client_id_foreign
		foreign key (client_id) references oauth2_client (id)
			on delete cascade
)
collate=utf8_unicode_ci
;

create index oauth2_exception_trail_client_id_index
	on oauth2_exception_trail (client_id)
;

create table if not exists oauth2_refresh_token
(
	id bigint unsigned auto_increment
		primary key,
	value varchar(255) not null,
	from_ip varchar(255) not null,
	lifetime int not null,
	scope text not null,
	audience text not null,
	void tinyint(1) default '0' not null,
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	client_id bigint unsigned not null,
	user_id bigint unsigned null,
	constraint oauth2_refresh_token_value_unique
		unique (value),
	constraint oauth2_refresh_token_client_id_foreign
		foreign key (client_id) references oauth2_client (id),
	constraint oauth2_refresh_token_user_id_foreign
		foreign key (user_id) references openid_users (id)
			on delete cascade
)
collate=utf8_unicode_ci
;

create table if not exists oauth2_access_token
(
	id bigint unsigned auto_increment
		primary key,
	value varchar(255) not null,
	from_ip varchar(255) not null,
	associated_authorization_code varchar(255) null,
	lifetime int not null,
	scope text not null,
	audience text not null,
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	client_id bigint unsigned not null,
	refresh_token_id bigint unsigned null,
	user_id bigint unsigned null,
	constraint oauth2_access_token_value_unique
		unique (value),
	constraint oauth2_access_token_client_id_foreign
		foreign key (client_id) references oauth2_client (id)
			on update cascade on delete cascade,
	constraint oauth2_access_token_refresh_token_id_foreign
		foreign key (refresh_token_id) references oauth2_refresh_token (id)
			on delete cascade,
	constraint oauth2_access_token_user_id_foreign
		foreign key (user_id) references openid_users (id)
			on delete cascade
)
collate=utf8_unicode_ci
;

create index oauth2_access_token_client_id_index
	on oauth2_access_token (client_id)
;

create index oauth2_access_token_refresh_token_id_index
	on oauth2_access_token (refresh_token_id)
;

create index oauth2_access_token_user_id_index
	on oauth2_access_token (user_id)
;

create index oauth2_refresh_token_client_id_index
	on oauth2_refresh_token (client_id)
;

create index oauth2_refresh_token_user_id_index
	on oauth2_refresh_token (user_id)
;

create table if not exists oauth2_user_consents
(
	id bigint unsigned auto_increment
		primary key,
	scopes text not null,
	client_id bigint unsigned not null,
	user_id bigint unsigned not null,
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	constraint oauth2_user_consents_client_id_foreign
		foreign key (client_id) references oauth2_client (id)
			on delete cascade,
	constraint oauth2_user_consents_user_id_foreign
		foreign key (user_id) references openid_users (id)
			on delete cascade
)
collate=utf8_unicode_ci
;

create index oauth2_user_consents_client_id_index
	on oauth2_user_consents (client_id)
;

create index oauth2_user_consents_user_id_index
	on oauth2_user_consents (user_id)
;

create table if not exists openid_trusted_sites
(
	id bigint unsigned auto_increment
		primary key,
	realm varchar(1024) not null,
	data text null,
	policy varchar(255) not null,
	user_id bigint unsigned not null,
	constraint openid_trusted_sites_user_id_foreign
		foreign key (user_id) references openid_users (id)
)
collate=utf8_unicode_ci
;

create index openid_trusted_sites_user_id_index
	on openid_trusted_sites (user_id)
;

create table if not exists server_configuration
(
	id bigint unsigned auto_increment
		primary key,
	`key` varchar(254) not null,
	value varchar(1024) not null
)
collate=utf8_unicode_ci
;

create table if not exists server_extensions
(
	id bigint unsigned auto_increment
		primary key,
	name varchar(100) not null,
	namespace varchar(255) not null,
	active tinyint(1) default '0' not null,
	extension_class varchar(255) not null,
	description varchar(255) null,
	view_name varchar(255) not null,
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null
)
collate=utf8_unicode_ci
;

create table if not exists user_actions
(
	id bigint unsigned auto_increment
		primary key,
	from_ip varchar(254) not null,
	realm varchar(1024) null,
	user_action varchar(512) not null,
	user_id bigint unsigned not null,
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	constraint user_actions_user_id_foreign
		foreign key (user_id) references openid_users (id)
)
collate=utf8_unicode_ci
;

create index user_actions_user_id_index
	on user_actions (user_id)
;

create table if not exists user_exceptions_trail
(
	id bigint unsigned auto_increment
		primary key,
	from_ip varchar(254) not null,
	exception_type varchar(1024) not null,
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null,
	user_id bigint unsigned null,
	stack_trace longtext null,
	constraint user_exceptions_trail_user_id_foreign
		foreign key (user_id) references openid_users (id)
			on delete cascade
)
collate=utf8_unicode_ci
;

create index user_exceptions_trail_user_id_index
	on user_exceptions_trail (user_id)
;

create table if not exists white_listed_ips
(
	id bigint unsigned auto_increment
		primary key,
	ip text not null,
	created_at timestamp default CURRENT_TIMESTAMP not null,
	updated_at timestamp default CURRENT_TIMESTAMP not null
)
collate=utf8_unicode_ci
;

SQL;

        if(!$schema->hasTable("openid_users")) {
            foreach (explode(";", $initial_state) as $sql_statement) {
                $sql_statement = trim($sql_statement);
                if (empty($sql_statement)) continue;
                $this->addSql($sql_statement);
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        (new Builder($schema))->drop('initial');
    }
}
