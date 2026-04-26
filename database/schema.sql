-- ============================================================
-- نظام ادارة الشواهد الذكي – Supabase Database Schema
-- Run this in the Supabase SQL Editor
-- ============================================================

-- ── Enable UUID extension ─────────────────────────────────
create extension if not exists "pgcrypto";

-- ── programs table ────────────────────────────────────────
-- Represents a program or event to which evidence files belong.
create table if not exists public.programs (
    id           uuid        primary key default gen_random_uuid(),
    user_id      uuid        not null references auth.users(id) on delete cascade,
    program_name text        not null,
    description  text,
    organization text        not null,
    slug         varchar(32) not null unique, -- shared-link token
    created_at   timestamptz default now()
);

-- ── evidence_files table ──────────────────────────────────
-- Stores the individual media/document files attached to a program.
create table if not exists public.evidence_files (
    id          uuid        primary key default gen_random_uuid(),
    program_id  uuid        not null references public.programs(id) on delete cascade,
    file_name   text        not null,       -- original filename shown to the user
    file_url    text        not null,       -- Supabase Storage public URL
    file_type   text        not null,       -- 'image' | 'video' | 'pdf' | 'other'
    file_size   bigint,                     -- bytes
    created_at  timestamptz default now()
);

-- ── Indexes ───────────────────────────────────────────────
create index if not exists idx_programs_user_id  on public.programs(user_id);
create index if not exists idx_programs_slug      on public.programs(slug);
create index if not exists idx_programs_created   on public.programs(created_at desc);
create index if not exists idx_evidence_program   on public.evidence_files(program_id);

-- ── Row Level Security – programs ─────────────────────────
alter table public.programs enable row level security;

create policy "Owners can view own programs"
    on public.programs for select
    using (auth.uid() = user_id);

create policy "Public can view programs by slug"
    on public.programs for select
    using (true);

create policy "Owners can insert programs"
    on public.programs for insert
    with check (auth.uid() = user_id);

create policy "Owners can delete own programs"
    on public.programs for delete
    using (auth.uid() = user_id);

-- ── Row Level Security – evidence_files ───────────────────
alter table public.evidence_files enable row level security;

create policy "Evidence visible to program owner"
    on public.evidence_files for select
    using (
        exists (
            select 1 from public.programs p
            where p.id = program_id and p.user_id = auth.uid()
        )
    );

create policy "Public can view evidence files"
    on public.evidence_files for select
    using (true);

create policy "Owners can insert evidence files"
    on public.evidence_files for insert
    with check (
        exists (
            select 1 from public.programs p
            where p.id = program_id and p.user_id = auth.uid()
        )
    );

create policy "Owners can delete evidence files"
    on public.evidence_files for delete
    using (
        exists (
            select 1 from public.programs p
            where p.id = program_id and p.user_id = auth.uid()
        )
    );

-- ── Supabase Storage bucket ───────────────────────────────
-- Dashboard → Storage → New Bucket
-- Name: evidence   (public: true)

-- ============================================================
-- user_profiles view
-- ============================================================
create or replace view public.user_profiles as
    select
        id,
        email,
        raw_user_meta_data->>'full_name' as full_name,
        created_at
    from auth.users;

