{{--
    Liste des documents joints à une tâche, téléchargeables.
    Usage : @include('partials.documents-tache', ['tache' => $tache, 'liste' => 'fichiers'])
    Option : 'retirable' => true affiche une case « retirer » (formulaire de modification).
--}}
@php
    use App\Support\DocumentsTache;
    $documents = $tache->$liste ?? [];
@endphp

@if($documents)
    <ul class="list-group list-group-flush border rounded" style="font-size:13px;">
        @foreach($documents as $index => $doc)
            <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                <a href="{{ route('taches.document', ['tache' => $tache->id, 'liste' => $liste, 'index' => $index]) }}"
                   class="text-decoration-none text-truncate">
                    {{ DocumentsTache::icone($doc['nom']) }} {{ $doc['nom'] }}
                </a>
                <span class="d-flex align-items-center gap-2 flex-shrink-0">
                    <span class="text-muted">{{ DocumentsTache::tailleLisible($doc['taille'] ?? null) }}</span>
                    @if(! empty($retirable))
                        <label class="form-check-label text-danger" style="font-size:12px;">
                            <input type="checkbox" class="form-check-input me-1" name="retirer_fichiers[]" value="{{ $doc['chemin'] }}">
                            {{ __('Retirer') }}
                        </label>
                    @endif
                </span>
            </li>
        @endforeach
    </ul>
@endif
