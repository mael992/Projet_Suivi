@extends('layouts.app')

@section('title', 'Logs d\'activité — Admin MGDS')

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-0" style="font-size:1.3rem;">📋 Logs d'activité</h2>
            <p class="text-muted small mb-0">
                Conservation : 6 mois (conformité CNIL/RGPD) — Sauvegarde automatique toutes les 48h
            </p>
        </div>
        @if($selected)
        <a href="{{ route('admin.logs.download', ['file' => $selected]) }}"
           class="btn btn-outline-dark btn-sm">
            ⬇️ Télécharger ce fichier
        </a>
        @endif
    </div>

    <div class="row g-3">

        {{-- ── Colonne gauche : liste des fichiers + sauvegardes ── --}}
        <div class="col-12 col-md-3">

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header py-2 px-3" style="background:#111;color:white;font-size:13px;font-weight:600;">
                    Fichiers de logs
                </div>
                <div class="list-group list-group-flush">
                    @forelse($files as $f)
                        @php $fname = basename($f); @endphp
                        <a href="{{ route('admin.logs.index', ['file' => $fname, 'cat' => $filterCat, 'q' => $filterText]) }}"
                           class="list-group-item list-group-item-action py-2 px-3 {{ $selected === $fname ? 'active' : '' }}"
                           style="font-size:13px;">
                            {{ $fname }}
                        </a>
                    @empty
                        <div class="list-group-item text-muted small py-2 px-3">Aucun fichier</div>
                    @endforelse
                </div>
            </div>

            @if(!empty($backups))
            <div class="card border-0 shadow-sm">
                <div class="card-header py-2 px-3" style="background:#555;color:white;font-size:12px;font-weight:600;">
                    Sauvegardes (48h)
                </div>
                <div class="list-group list-group-flush" style="max-height:200px;overflow-y:auto;">
                    @foreach($backups as $backup)
                        <div class="list-group-item py-1 px-3" style="font-size:11px;color:#555;">
                            📦 {{ $backup }}
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>

        {{-- ── Colonne droite : contenu + filtres ── --}}
        <div class="col-12 col-md-9">

            {{-- Filtres --}}
            <form method="GET" action="{{ route('admin.logs.index') }}" class="card border-0 shadow-sm mb-3">
                <div class="card-body py-2 px-3">
                    <div class="row g-2 align-items-end">
                        <input type="hidden" name="file" value="{{ $selected }}">
                        <div class="col-12 col-sm-4">
                            <label class="form-label small fw-semibold mb-1">Catégorie</label>
                            <select name="cat" class="form-select form-select-sm">
                                <option value="">Toutes</option>
                                @foreach(['AUTH','USER','TICKET','INCIDENT','CHANTIER','SYSTEM'] as $cat)
                                    <option value="{{ $cat }}" {{ $filterCat === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold mb-1">Recherche</label>
                            <input type="text" name="q" class="form-control form-control-sm"
                                   placeholder="Nom, IP, action..." value="{{ $filterText }}">
                        </div>
                        <div class="col-12 col-sm-2">
                            <button type="submit" class="btn btn-dark btn-sm w-100">Filtrer</button>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Contenu log --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center"
                     style="background:#f8f9fa;font-size:13px;">
                    <span class="fw-semibold">{{ $selected ?? 'Aucun fichier sélectionné' }}</span>
                    <span class="text-muted small">{{ count($lines) }} ligne(s)</span>
                </div>
                <div class="card-body p-0">
                    @if(empty($lines))
                        <div class="text-center text-muted py-5">Aucune entrée à afficher.</div>
                    @else
                        <div style="overflow-x:auto;max-height:600px;overflow-y:auto;">
                            <table class="table table-sm mb-0" style="font-size:12px;font-family:monospace;">
                                <tbody>
                                @foreach($lines as $line)
                                    @php
                                        // Colorisation selon catégorie
                                        $color = '#333';
                                        if (str_contains($line, '[AUTH]'))     $color = '#1a56db';
                                        if (str_contains($line, '[USER]'))     $color = '#7e3af2';
                                        if (str_contains($line, '[TICKET]'))   $color = '#c27803';
                                        if (str_contains($line, '[INCIDENT]')) $color = '#e02424';
                                        if (str_contains($line, '[CHANTIER]')) $color = '#057a55';
                                        if (str_contains($line, '[SYSTEM]'))   $color = '#6b7280';
                                    @endphp
                                    <tr>
                                        <td style="color:{{ $color }};white-space:nowrap;padding:3px 12px;border:none;line-height:1.5;">
                                            {{ $line }}
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
