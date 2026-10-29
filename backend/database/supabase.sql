-- Esquema de CodeFlow para Supabase (Postgres), generado a partir de las migraciones de Laravel.
-- Solo para una base de datos VACÍA: pegar en Supabase -> SQL Editor -> Run. Incluye la tabla migrations ya rellenada.
-- Si se añaden migraciones, se regenera (ver docs/DESPLIEGUE.md) o se ejecutan solo las nuevas.

-- PostgreSQL database dump


-- Dumped from database version 16.15 (Ubuntu 16.15-0ubuntu0.24.04.1)
-- Dumped by pg_dump version 16.15 (Ubuntu 16.15-0ubuntu0.24.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

-- Name: activity_logs; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.activity_logs (
    id bigint NOT NULL,
    project_id bigint NOT NULL,
    user_id bigint,
    action character varying(60) NOT NULL,
    subject_type character varying(255),
    subject_id bigint,
    properties json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

-- Name: activity_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.activity_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: activity_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.activity_logs_id_seq OWNED BY public.activity_logs.id;

-- Name: ai_analyses; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.ai_analyses (
    id bigint NOT NULL,
    pull_request_id bigint NOT NULL,
    requested_by bigint,
    status character varying(16) DEFAULT 'pending'::character varying NOT NULL,
    model character varying(100) NOT NULL,
    head_commit_id bigint,
    summary text,
    error character varying(500),
    files_analyzed smallint DEFAULT '0'::smallint NOT NULL,
    files_skipped smallint DEFAULT '0'::smallint NOT NULL,
    findings_count smallint DEFAULT '0'::smallint NOT NULL,
    started_at timestamp(0) without time zone,
    finished_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    batches_total smallint DEFAULT '0'::smallint NOT NULL,
    batches_done smallint DEFAULT '0'::smallint NOT NULL,
    files_truncated smallint DEFAULT '0'::smallint NOT NULL,
    skipped_files json
);

-- Name: ai_analyses_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.ai_analyses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: ai_analyses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.ai_analyses_id_seq OWNED BY public.ai_analyses.id;

-- Name: ai_findings; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.ai_findings (
    id bigint NOT NULL,
    ai_analysis_id bigint NOT NULL,
    path character varying(1024) NOT NULL,
    line integer,
    severity character varying(10) NOT NULL,
    category character varying(20) NOT NULL,
    title character varying(200) NOT NULL,
    explanation text NOT NULL,
    suggestion text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

-- Name: ai_findings_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.ai_findings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: ai_findings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.ai_findings_id_seq OWNED BY public.ai_findings.id;

-- Name: blobs; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.blobs (
    id bigint NOT NULL,
    repository_id bigint NOT NULL,
    content_mac character(64) NOT NULL,
    content text NOT NULL,
    size integer NOT NULL,
    is_binary boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

-- Name: blobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.blobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: blobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.blobs_id_seq OWNED BY public.blobs.id;

-- Name: branches; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.branches (
    id bigint NOT NULL,
    repository_id bigint NOT NULL,
    name character varying(150) NOT NULL,
    head_commit_id bigint,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

-- Name: branches_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.branches_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: branches_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.branches_id_seq OWNED BY public.branches.id;

-- Name: cache; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);

-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);

-- Name: commit_files; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.commit_files (
    id bigint NOT NULL,
    commit_id bigint NOT NULL,
    path character varying(255) NOT NULL,
    blob_id bigint NOT NULL
);

-- Name: commit_files_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.commit_files_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: commit_files_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.commit_files_id_seq OWNED BY public.commit_files.id;

-- Name: commits; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.commits (
    id bigint NOT NULL,
    repository_id bigint NOT NULL,
    parent_id bigint,
    second_parent_id bigint,
    author_id bigint,
    sha character varying(64) NOT NULL,
    message text NOT NULL,
    created_at timestamp(6) without time zone,
    updated_at timestamp(6) without time zone
);

-- Name: commits_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.commits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: commits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.commits_id_seq OWNED BY public.commits.id;

-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection character varying(255) NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);

-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;

-- Name: issue_comments; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.issue_comments (
    id bigint NOT NULL,
    issue_id bigint NOT NULL,
    user_id bigint,
    body text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

-- Name: issue_comments_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.issue_comments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: issue_comments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.issue_comments_id_seq OWNED BY public.issue_comments.id;

-- Name: issue_pull_request; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.issue_pull_request (
    issue_id bigint NOT NULL,
    pull_request_id bigint NOT NULL
);

-- Name: issues; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.issues (
    id bigint NOT NULL,
    repository_id bigint NOT NULL,
    number integer NOT NULL,
    title character varying(200) NOT NULL,
    description text,
    author_id bigint,
    assignee_id bigint,
    priority character varying(20) DEFAULT 'medium'::character varying NOT NULL,
    status character varying(20) DEFAULT 'open'::character varying NOT NULL,
    labels json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

-- Name: issues_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.issues_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: issues_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.issues_id_seq OWNED BY public.issues.id;

-- Name: job_batches; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);

-- Name: jobs; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);

-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;

-- Name: migrations; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);

-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;

-- Name: notifications; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.notifications (
    id uuid NOT NULL,
    type character varying(255) NOT NULL,
    notifiable_type character varying(255) NOT NULL,
    notifiable_id bigint NOT NULL,
    data text NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);

-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name text NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;

-- Name: project_invitations; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.project_invitations (
    id bigint NOT NULL,
    project_id bigint NOT NULL,
    email character varying(255) NOT NULL,
    role character varying(20) NOT NULL,
    token_hash character(64) NOT NULL,
    invited_by bigint,
    expires_at timestamp(0) without time zone NOT NULL,
    accepted_at timestamp(0) without time zone,
    accepted_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

-- Name: project_invitations_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.project_invitations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: project_invitations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.project_invitations_id_seq OWNED BY public.project_invitations.id;

-- Name: project_members; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.project_members (
    id bigint NOT NULL,
    project_id bigint NOT NULL,
    user_id bigint NOT NULL,
    role character varying(20) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

-- Name: project_members_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.project_members_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: project_members_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.project_members_id_seq OWNED BY public.project_members.id;

-- Name: projects; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.projects (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    description text,
    owner_id bigint NOT NULL,
    settings json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);

-- Name: projects_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.projects_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: projects_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.projects_id_seq OWNED BY public.projects.id;

-- Name: pull_request_comments; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.pull_request_comments (
    id bigint NOT NULL,
    pull_request_id bigint NOT NULL,
    user_id bigint,
    body text NOT NULL,
    path character varying(255),
    line integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

-- Name: pull_request_comments_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.pull_request_comments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: pull_request_comments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.pull_request_comments_id_seq OWNED BY public.pull_request_comments.id;

-- Name: pull_request_reviewers; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.pull_request_reviewers (
    pull_request_id bigint NOT NULL,
    user_id bigint NOT NULL
);

-- Name: pull_request_reviews; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.pull_request_reviews (
    id bigint NOT NULL,
    pull_request_id bigint NOT NULL,
    user_id bigint NOT NULL,
    state character varying(20) NOT NULL,
    body text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

-- Name: pull_request_reviews_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.pull_request_reviews_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: pull_request_reviews_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.pull_request_reviews_id_seq OWNED BY public.pull_request_reviews.id;

-- Name: pull_requests; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.pull_requests (
    id bigint NOT NULL,
    repository_id bigint NOT NULL,
    number integer NOT NULL,
    title character varying(200) NOT NULL,
    description text,
    author_id bigint,
    source_branch_id bigint,
    target_branch_id bigint,
    source_branch_name character varying(150) NOT NULL,
    target_branch_name character varying(150) NOT NULL,
    status character varying(20) DEFAULT 'open'::character varying NOT NULL,
    merge_commit_id bigint,
    merged_by bigint,
    merged_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

-- Name: pull_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.pull_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: pull_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.pull_requests_id_seq OWNED BY public.pull_requests.id;

-- Name: repositories; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.repositories (
    id bigint NOT NULL,
    project_id bigint NOT NULL,
    name character varying(100) NOT NULL,
    description text,
    default_branch character varying(100) DEFAULT 'main'::character varying NOT NULL,
    visibility character varying(10) DEFAULT 'private'::character varying NOT NULL,
    encrypted_key text NOT NULL,
    key_version smallint DEFAULT '1'::smallint NOT NULL,
    size_bytes bigint DEFAULT '0'::bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);

-- Name: repositories_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.repositories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: repositories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.repositories_id_seq OWNED BY public.repositories.id;

-- Name: sessions; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);

-- Name: users; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    is_admin boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    email_verification_code character varying(255),
    email_verification_expires_at timestamp(0) without time zone,
    email_verification_attempts smallint DEFAULT '0'::smallint NOT NULL,
    onboarding json,
    preferences json
);

-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;

-- Name: activity_logs id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.activity_logs ALTER COLUMN id SET DEFAULT nextval('public.activity_logs_id_seq'::regclass);

-- Name: ai_analyses id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.ai_analyses ALTER COLUMN id SET DEFAULT nextval('public.ai_analyses_id_seq'::regclass);

-- Name: ai_findings id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.ai_findings ALTER COLUMN id SET DEFAULT nextval('public.ai_findings_id_seq'::regclass);

-- Name: blobs id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.blobs ALTER COLUMN id SET DEFAULT nextval('public.blobs_id_seq'::regclass);

-- Name: branches id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.branches ALTER COLUMN id SET DEFAULT nextval('public.branches_id_seq'::regclass);

-- Name: commit_files id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.commit_files ALTER COLUMN id SET DEFAULT nextval('public.commit_files_id_seq'::regclass);

-- Name: commits id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.commits ALTER COLUMN id SET DEFAULT nextval('public.commits_id_seq'::regclass);

-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);

-- Name: issue_comments id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.issue_comments ALTER COLUMN id SET DEFAULT nextval('public.issue_comments_id_seq'::regclass);

-- Name: issues id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.issues ALTER COLUMN id SET DEFAULT nextval('public.issues_id_seq'::regclass);

-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);

-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);

-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);

-- Name: project_invitations id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.project_invitations ALTER COLUMN id SET DEFAULT nextval('public.project_invitations_id_seq'::regclass);

-- Name: project_members id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.project_members ALTER COLUMN id SET DEFAULT nextval('public.project_members_id_seq'::regclass);

-- Name: projects id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.projects ALTER COLUMN id SET DEFAULT nextval('public.projects_id_seq'::regclass);

-- Name: pull_request_comments id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.pull_request_comments ALTER COLUMN id SET DEFAULT nextval('public.pull_request_comments_id_seq'::regclass);

-- Name: pull_request_reviews id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.pull_request_reviews ALTER COLUMN id SET DEFAULT nextval('public.pull_request_reviews_id_seq'::regclass);

-- Name: pull_requests id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.pull_requests ALTER COLUMN id SET DEFAULT nextval('public.pull_requests_id_seq'::regclass);

-- Name: repositories id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.repositories ALTER COLUMN id SET DEFAULT nextval('public.repositories_id_seq'::regclass);

-- Name: users id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);

-- Name: activity_logs activity_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.activity_logs
    ADD CONSTRAINT activity_logs_pkey PRIMARY KEY (id);

-- Name: ai_analyses ai_analyses_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.ai_analyses
    ADD CONSTRAINT ai_analyses_pkey PRIMARY KEY (id);

-- Name: ai_findings ai_findings_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.ai_findings
    ADD CONSTRAINT ai_findings_pkey PRIMARY KEY (id);

-- Name: blobs blobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.blobs
    ADD CONSTRAINT blobs_pkey PRIMARY KEY (id);

-- Name: blobs blobs_repository_id_content_mac_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.blobs
    ADD CONSTRAINT blobs_repository_id_content_mac_unique UNIQUE (repository_id, content_mac);

-- Name: branches branches_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.branches
    ADD CONSTRAINT branches_pkey PRIMARY KEY (id);

-- Name: branches branches_repository_id_name_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.branches
    ADD CONSTRAINT branches_repository_id_name_unique UNIQUE (repository_id, name);

-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);

-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);

-- Name: commit_files commit_files_commit_id_path_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.commit_files
    ADD CONSTRAINT commit_files_commit_id_path_unique UNIQUE (commit_id, path);

-- Name: commit_files commit_files_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.commit_files
    ADD CONSTRAINT commit_files_pkey PRIMARY KEY (id);

-- Name: commits commits_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.commits
    ADD CONSTRAINT commits_pkey PRIMARY KEY (id);

-- Name: commits commits_repository_id_sha_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.commits
    ADD CONSTRAINT commits_repository_id_sha_unique UNIQUE (repository_id, sha);

-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);

-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);

-- Name: issue_comments issue_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.issue_comments
    ADD CONSTRAINT issue_comments_pkey PRIMARY KEY (id);

-- Name: issue_pull_request issue_pull_request_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.issue_pull_request
    ADD CONSTRAINT issue_pull_request_pkey PRIMARY KEY (issue_id, pull_request_id);

-- Name: issues issues_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.issues
    ADD CONSTRAINT issues_pkey PRIMARY KEY (id);

-- Name: issues issues_repository_id_number_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.issues
    ADD CONSTRAINT issues_repository_id_number_unique UNIQUE (repository_id, number);

-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);

-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);

-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);

-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);

-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);

-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);

-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);

-- Name: project_invitations project_invitations_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.project_invitations
    ADD CONSTRAINT project_invitations_pkey PRIMARY KEY (id);

-- Name: project_invitations project_invitations_token_hash_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.project_invitations
    ADD CONSTRAINT project_invitations_token_hash_unique UNIQUE (token_hash);

-- Name: project_members project_members_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.project_members
    ADD CONSTRAINT project_members_pkey PRIMARY KEY (id);

-- Name: project_members project_members_project_id_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.project_members
    ADD CONSTRAINT project_members_project_id_user_id_unique UNIQUE (project_id, user_id);

-- Name: projects projects_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_pkey PRIMARY KEY (id);

-- Name: pull_request_comments pull_request_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pull_request_comments
    ADD CONSTRAINT pull_request_comments_pkey PRIMARY KEY (id);

-- Name: pull_request_reviewers pull_request_reviewers_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pull_request_reviewers
    ADD CONSTRAINT pull_request_reviewers_pkey PRIMARY KEY (pull_request_id, user_id);

-- Name: pull_request_reviews pull_request_reviews_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pull_request_reviews
    ADD CONSTRAINT pull_request_reviews_pkey PRIMARY KEY (id);

-- Name: pull_requests pull_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pull_requests
    ADD CONSTRAINT pull_requests_pkey PRIMARY KEY (id);

-- Name: pull_requests pull_requests_repository_id_number_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pull_requests
    ADD CONSTRAINT pull_requests_repository_id_number_unique UNIQUE (repository_id, number);

-- Name: repositories repositories_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.repositories
    ADD CONSTRAINT repositories_pkey PRIMARY KEY (id);

-- Name: repositories repositories_project_id_name_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.repositories
    ADD CONSTRAINT repositories_project_id_name_unique UNIQUE (project_id, name);

-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);

-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);

-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);

-- Name: activity_logs_subject_type_subject_id_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX activity_logs_subject_type_subject_id_index ON public.activity_logs USING btree (subject_type, subject_id);

-- Name: ai_analyses_pull_request_id_id_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX ai_analyses_pull_request_id_id_index ON public.ai_analyses USING btree (pull_request_id, id);

-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);

-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);

-- Name: commits_parent_id_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX commits_parent_id_index ON public.commits USING btree (parent_id);

-- Name: commits_repository_id_created_at_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX commits_repository_id_created_at_index ON public.commits USING btree (repository_id, created_at);

-- Name: failed_jobs_connection_queue_failed_at_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX failed_jobs_connection_queue_failed_at_index ON public.failed_jobs USING btree (connection, queue, failed_at);

-- Name: issues_repository_id_status_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX issues_repository_id_status_index ON public.issues USING btree (repository_id, status);

-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);

-- Name: notifications_notifiable_type_notifiable_id_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX notifications_notifiable_type_notifiable_id_index ON public.notifications USING btree (notifiable_type, notifiable_id);

-- Name: notifications_unread_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX notifications_unread_index ON public.notifications USING btree (notifiable_type, notifiable_id, read_at);

-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX personal_access_tokens_expires_at_index ON public.personal_access_tokens USING btree (expires_at);

-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);

-- Name: project_invitations_email_accepted_at_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX project_invitations_email_accepted_at_index ON public.project_invitations USING btree (email, accepted_at);

-- Name: project_invitations_project_id_accepted_at_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX project_invitations_project_id_accepted_at_index ON public.project_invitations USING btree (project_id, accepted_at);

-- Name: projects_deleted_at_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX projects_deleted_at_index ON public.projects USING btree (deleted_at);

-- Name: pull_requests_repository_id_status_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX pull_requests_repository_id_status_index ON public.pull_requests USING btree (repository_id, status);

-- Name: repositories_deleted_at_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX repositories_deleted_at_index ON public.repositories USING btree (deleted_at);

-- Name: repositories_visibility_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX repositories_visibility_index ON public.repositories USING btree (visibility);

-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);

-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);

-- Name: activity_logs activity_logs_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.activity_logs
    ADD CONSTRAINT activity_logs_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;

-- Name: activity_logs activity_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.activity_logs
    ADD CONSTRAINT activity_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;

-- Name: ai_analyses ai_analyses_head_commit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.ai_analyses
    ADD CONSTRAINT ai_analyses_head_commit_id_foreign FOREIGN KEY (head_commit_id) REFERENCES public.commits(id) ON DELETE SET NULL;

-- Name: ai_analyses ai_analyses_pull_request_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.ai_analyses
    ADD CONSTRAINT ai_analyses_pull_request_id_foreign FOREIGN KEY (pull_request_id) REFERENCES public.pull_requests(id) ON DELETE CASCADE;

-- Name: ai_analyses ai_analyses_requested_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.ai_analyses
    ADD CONSTRAINT ai_analyses_requested_by_foreign FOREIGN KEY (requested_by) REFERENCES public.users(id) ON DELETE SET NULL;

-- Name: ai_findings ai_findings_ai_analysis_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.ai_findings
    ADD CONSTRAINT ai_findings_ai_analysis_id_foreign FOREIGN KEY (ai_analysis_id) REFERENCES public.ai_analyses(id) ON DELETE CASCADE;

-- Name: blobs blobs_repository_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.blobs
    ADD CONSTRAINT blobs_repository_id_foreign FOREIGN KEY (repository_id) REFERENCES public.repositories(id) ON DELETE CASCADE;

-- Name: branches branches_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.branches
    ADD CONSTRAINT branches_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;

-- Name: branches branches_repository_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.branches
    ADD CONSTRAINT branches_repository_id_foreign FOREIGN KEY (repository_id) REFERENCES public.repositories(id) ON DELETE CASCADE;

-- Name: commit_files commit_files_blob_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.commit_files
    ADD CONSTRAINT commit_files_blob_id_foreign FOREIGN KEY (blob_id) REFERENCES public.blobs(id) ON DELETE CASCADE;

-- Name: commit_files commit_files_commit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.commit_files
    ADD CONSTRAINT commit_files_commit_id_foreign FOREIGN KEY (commit_id) REFERENCES public.commits(id) ON DELETE CASCADE;

-- Name: commits commits_author_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.commits
    ADD CONSTRAINT commits_author_id_foreign FOREIGN KEY (author_id) REFERENCES public.users(id) ON DELETE SET NULL;

-- Name: commits commits_repository_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.commits
    ADD CONSTRAINT commits_repository_id_foreign FOREIGN KEY (repository_id) REFERENCES public.repositories(id) ON DELETE CASCADE;

-- Name: issue_comments issue_comments_issue_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.issue_comments
    ADD CONSTRAINT issue_comments_issue_id_foreign FOREIGN KEY (issue_id) REFERENCES public.issues(id) ON DELETE CASCADE;

-- Name: issue_comments issue_comments_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.issue_comments
    ADD CONSTRAINT issue_comments_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;

-- Name: issue_pull_request issue_pull_request_issue_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.issue_pull_request
    ADD CONSTRAINT issue_pull_request_issue_id_foreign FOREIGN KEY (issue_id) REFERENCES public.issues(id) ON DELETE CASCADE;

-- Name: issue_pull_request issue_pull_request_pull_request_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.issue_pull_request
    ADD CONSTRAINT issue_pull_request_pull_request_id_foreign FOREIGN KEY (pull_request_id) REFERENCES public.pull_requests(id) ON DELETE CASCADE;

-- Name: issues issues_assignee_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.issues
    ADD CONSTRAINT issues_assignee_id_foreign FOREIGN KEY (assignee_id) REFERENCES public.users(id) ON DELETE SET NULL;

-- Name: issues issues_author_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.issues
    ADD CONSTRAINT issues_author_id_foreign FOREIGN KEY (author_id) REFERENCES public.users(id) ON DELETE SET NULL;

-- Name: issues issues_repository_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.issues
    ADD CONSTRAINT issues_repository_id_foreign FOREIGN KEY (repository_id) REFERENCES public.repositories(id) ON DELETE CASCADE;

-- Name: project_invitations project_invitations_accepted_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.project_invitations
    ADD CONSTRAINT project_invitations_accepted_by_foreign FOREIGN KEY (accepted_by) REFERENCES public.users(id) ON DELETE SET NULL;

-- Name: project_invitations project_invitations_invited_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.project_invitations
    ADD CONSTRAINT project_invitations_invited_by_foreign FOREIGN KEY (invited_by) REFERENCES public.users(id) ON DELETE SET NULL;

-- Name: project_invitations project_invitations_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.project_invitations
    ADD CONSTRAINT project_invitations_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;

-- Name: project_members project_members_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.project_members
    ADD CONSTRAINT project_members_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;

-- Name: project_members project_members_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.project_members
    ADD CONSTRAINT project_members_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;

-- Name: projects projects_owner_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_owner_id_foreign FOREIGN KEY (owner_id) REFERENCES public.users(id) ON DELETE CASCADE;

-- Name: pull_request_comments pull_request_comments_pull_request_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pull_request_comments
    ADD CONSTRAINT pull_request_comments_pull_request_id_foreign FOREIGN KEY (pull_request_id) REFERENCES public.pull_requests(id) ON DELETE CASCADE;

-- Name: pull_request_comments pull_request_comments_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pull_request_comments
    ADD CONSTRAINT pull_request_comments_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;

-- Name: pull_request_reviewers pull_request_reviewers_pull_request_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pull_request_reviewers
    ADD CONSTRAINT pull_request_reviewers_pull_request_id_foreign FOREIGN KEY (pull_request_id) REFERENCES public.pull_requests(id) ON DELETE CASCADE;

-- Name: pull_request_reviewers pull_request_reviewers_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pull_request_reviewers
    ADD CONSTRAINT pull_request_reviewers_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;

-- Name: pull_request_reviews pull_request_reviews_pull_request_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pull_request_reviews
    ADD CONSTRAINT pull_request_reviews_pull_request_id_foreign FOREIGN KEY (pull_request_id) REFERENCES public.pull_requests(id) ON DELETE CASCADE;

-- Name: pull_request_reviews pull_request_reviews_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pull_request_reviews
    ADD CONSTRAINT pull_request_reviews_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;

-- Name: pull_requests pull_requests_author_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pull_requests
    ADD CONSTRAINT pull_requests_author_id_foreign FOREIGN KEY (author_id) REFERENCES public.users(id) ON DELETE SET NULL;

-- Name: pull_requests pull_requests_merged_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pull_requests
    ADD CONSTRAINT pull_requests_merged_by_foreign FOREIGN KEY (merged_by) REFERENCES public.users(id) ON DELETE SET NULL;

-- Name: pull_requests pull_requests_repository_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pull_requests
    ADD CONSTRAINT pull_requests_repository_id_foreign FOREIGN KEY (repository_id) REFERENCES public.repositories(id) ON DELETE CASCADE;

-- Name: pull_requests pull_requests_source_branch_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pull_requests
    ADD CONSTRAINT pull_requests_source_branch_id_foreign FOREIGN KEY (source_branch_id) REFERENCES public.branches(id) ON DELETE SET NULL;

-- Name: pull_requests pull_requests_target_branch_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pull_requests
    ADD CONSTRAINT pull_requests_target_branch_id_foreign FOREIGN KEY (target_branch_id) REFERENCES public.branches(id) ON DELETE SET NULL;

-- Name: repositories repositories_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.repositories
    ADD CONSTRAINT repositories_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;

-- PostgreSQL database dump complete


-- PostgreSQL database dump


-- Dumped from database version 16.15 (Ubuntu 16.15-0ubuntu0.24.04.1)
-- Dumped by pg_dump version 16.15 (Ubuntu 16.15-0ubuntu0.24.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -

INSERT INTO public.migrations VALUES (1, '0001_01_01_000000_create_users_table', 1);
INSERT INTO public.migrations VALUES (2, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO public.migrations VALUES (3, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO public.migrations VALUES (4, '2026_10_08_000001_add_role_to_users_table', 1);
INSERT INTO public.migrations VALUES (5, '2026_10_08_000002_create_projects_tables', 1);
INSERT INTO public.migrations VALUES (6, '2026_10_08_000003_create_repositories_tables', 1);
INSERT INTO public.migrations VALUES (7, '2026_10_08_000004_create_issues_and_pull_requests_tables', 1);
INSERT INTO public.migrations VALUES (8, '2026_10_08_000006_create_notifications_table', 1);
INSERT INTO public.migrations VALUES (9, '2026_10_08_114629_create_personal_access_tokens_table', 1);
INSERT INTO public.migrations VALUES (10, '2026_10_09_000001_verify_existing_users', 1);
INSERT INTO public.migrations VALUES (11, '2026_10_09_000002_add_email_verification_code_to_users', 1);
INSERT INTO public.migrations VALUES (12, '2026_10_09_000003_add_unread_index_to_notifications', 1);
INSERT INTO public.migrations VALUES (13, '2026_10_15_000001_create_ai_analysis_tables', 1);
INSERT INTO public.migrations VALUES (14, '2026_10_23_000001_add_progress_to_ai_analyses', 1);
INSERT INTO public.migrations VALUES (15, '2026_10_30_000001_add_onboarding_to_users', 1);
INSERT INTO public.migrations VALUES (16, '2026_10_30_000002_add_preferences_to_users', 1);
INSERT INTO public.migrations VALUES (17, '2026_10_30_000003_create_project_invitations_table', 1);
INSERT INTO public.migrations VALUES (18, '2026_10_30_000004_add_soft_deletes_to_projects_and_repositories', 1);

-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -

SELECT pg_catalog.setval('public.migrations_id_seq', 18, true);

-- PostgreSQL database dump complete



-- Supabase: RLS activado en todas las tablas (la app entra como propietaria y no le afecta;
-- así la API pública de Supabase no expone datos).
ALTER TABLE public.activity_logs ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.ai_analyses ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.ai_findings ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.blobs ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.branches ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.cache ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.cache_locks ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.commit_files ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.commits ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.failed_jobs ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.issue_comments ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.issue_pull_request ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.issues ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.job_batches ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.jobs ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.migrations ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.notifications ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.password_reset_tokens ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.personal_access_tokens ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.project_invitations ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.project_members ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.projects ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pull_request_comments ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pull_request_reviewers ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pull_request_reviews ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pull_requests ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.repositories ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.sessions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.users ENABLE ROW LEVEL SECURITY;
