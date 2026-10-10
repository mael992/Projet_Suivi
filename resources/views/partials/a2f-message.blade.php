{{-- Double authentification : retour sur le formulaire après le code (ou
     erreur d'envoi du code), quelle que soit la page. La page de saisie du
     code affiche elle-même ses messages. --}}
@unless(request()->routeIs('a2f.*', 'contact.ticket.code'))
    @if(session('a2f_ok') || $errors->has('a2f'))
        <div class="container pt-3">
            @if(session('a2f_ok'))
                <div class="alert alert-success py-2 mb-0" style="font-size:14px;">🔐 {{ session('a2f_ok') }}</div>
            @endif
            @if($errors->has('a2f'))
                <div class="alert alert-danger py-2 mb-0" style="font-size:14px;">🔐 {{ $errors->first('a2f') }}</div>
            @endif
        </div>
    @endif
@endunless
