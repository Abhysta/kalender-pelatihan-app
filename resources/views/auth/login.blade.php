<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Akses Kalender</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="calendar-surface min-h-screen text-slate-800 antialiased">
    <main class="mx-auto flex min-h-screen max-w-7xl items-center px-4 py-8 sm:px-6 lg:px-8">
        <section class="w-full">
            <header class="calendar-card rounded-3xl border border-white/70 bg-white/90 p-6 shadow-2xl shadow-slate-900/10 sm:p-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <div class="inline-flex items-center gap-4 px-4 py-3">
                            <img src="{{ asset('jp.png') }}" alt="Logo Jendela Pembelajar" class="h-14 w-auto object-contain sm:h-16" />
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-teal-700">BPSDM Hukum</p>
                                <p class="mt-1 font-['Space_Grotesk'] text-lg font-bold text-slate-900 sm:text-xl">Jendela Pembelajar</p>
                                <p class="text-xs text-slate-500">Portal akses admin dan umum</p>
                            </div>
                        </div>
                    
                    </div>
                </div>
            </header>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <section class="calendar-card rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-xl shadow-slate-900/10 sm:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-700">Khusus Admin</p>
                            <h2 class="mt-2 font-['Space_Grotesk'] text-2xl font-bold text-slate-900 sm:text-3xl">Login untuk pengelola aplikasi</h2>
                        </div>
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-600 text-base font-bold text-white shadow-sm">A</span>
                    </div>

                    <p class="mt-4 text-sm leading-7 text-slate-600">
                        Masukkan akun admin untuk membuka dashboard, mengunggah data, dan mengelola informasi pelatihan.
                    </p>

                    @if ($errors->any())
                        <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form action="{{ route('admin.login.store') }}" method="POST" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <label for="email" class="mb-1 block text-sm font-semibold text-slate-700">Email Admin</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-teal-500" placeholder="admin@bpsdm.local" required />
                        </div>

                        <div>
                            <label for="password" class="mb-1 block text-sm font-semibold text-slate-700">Password</label>
                            <input id="password" name="password" type="password" class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-teal-500" placeholder="Masukkan password admin" required />
                        </div>

                        <label class="flex items-center gap-3 text-sm text-slate-600">
                            <input name="remember" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500" />
                            Ingat sesi login admin
                        </label>

                        <button type="submit" class="w-full rounded-xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-teal-700">
                            Login Admin
                        </button>
                    </form>
                </section>

                <a href="{{ route('katalog.index') }}" class="calendar-card rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-xl shadow-slate-900/10 transition hover:-translate-y-1 hover:border-amber-300 sm:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-700">Umum</p>
                            <h2 class="mt-2 font-['Space_Grotesk'] text-2xl font-bold text-slate-900 sm:text-3xl">Lihat katalog pelatihan dan jadwal kegiatan</h2>
                        </div>
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500 text-base font-bold text-white shadow-sm">U</span>
                    </div>

                    <p class="mt-4 text-sm leading-7 text-slate-600">
                        Digunakan untuk pengguna umum yang ingin melihat daftar pelatihan, detail agenda, dan informasi kalender.
                    </p>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <span class="rounded-full bg-amber-700 px-4 py-2 text-sm font-semibold text-white shadow-sm">Masuk Sebagai Umum</span>
                        <span class="text-sm text-slate-500">Akses publik aplikasi</span>
                    </div>
                </a>
            </div>
        </section>
    </main>
</body>
</html>
