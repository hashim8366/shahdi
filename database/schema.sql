-- ============================================================
-- نظام ادارة الشواهد الذكي – Supabase Database Schema
-- Run this in the Supabase SQL Editor
-- ============================================================

-- ── Enable UUID extension ─────────────────────────────────
create extension if not exists "pgcrypto";

-- ── certificates table ────────────────────────────────────
create table if not exists public.certificates (
    id               uuid        primary key default gen_random_uuid(),
    user_id          uuid        not null references auth.users(id) on delete cascade,
    program_name     text        not null,
    description      text,
    holder_name      text        not null,
    organization     text        not null,
    certificate_url  text,                       -- Supabase Storage public URL
    slug             varchar(32) not null unique, -- e.g. bin2hex(random_bytes(8))
    created_at       timestamptz default now()
);

-- ── Indexes ───────────────────────────────────────────────
create index if not exists idx_certificates_user_id  on public.certificates(user_id);
create index if not exists idx_certificates_slug      on public.certificates(slug);
create index if not exists idx_certificates_created   on public.certificates(created_at desc);

-- ── Row Level Security ────────────────────────────────────
alter table public.certificates enable row level security;

-- Owners can read their own certificates
create policy "Users can view own certificates"
    on public.certificates for select
    using (auth.uid() = user_id);

-- Public can read certificates by slug (for shared links)
create policy "Public can view certificates by slug"
    on public.certificates for select
    using (true);   -- RLS select is open; the slug is the access control mechanism

-- Owners can insert
create policy "Users can insert own certificates"
    on public.certificates for insert
    with check (auth.uid() = user_id);

-- Owners can delete their own certificates
create policy "Users can delete own certificates"
    on public.certificates for delete
    using (auth.uid() = user_id);

-- ── Supabase Storage bucket ───────────────────────────────
-- Run in the Supabase Dashboard → Storage → New Bucket
-- Name: certificates  (public: true)
-- Or execute via the Management API.

-- ============================================================
-- Optional: user_profiles view (uses auth.users metadata)
-- ============================================================
create or replace view public.user_profiles as
    select
        id,
        email,
        raw_user_meta_data->>'full_name' as full_name,
        created_at
    from auth.users;
