<div class="card  rounded"><div class="card-body">
            <dl class="row">
                <dt class="col-sm-4">ID</dt>
                <dd class="col-sm-8">{{ $salonReview->id }}</dd>
<dt class="col-sm-4">Review</dt>
                <dd class="col-sm-8">{{ $salonReview->review }}</dd>

<dt class="col-sm-4">Note</dt>
                <dd class="col-sm-8">
                    <span class="badge bg-warning text-dark">
                        {{ $salonReview->rate }} ★
                    </span>
                </dd>

<dt class="col-sm-4">Utilisateur</dt>
                <dd class="col-sm-8">#{{ $salonReview->user_id }}</dd>

<dt class="col-sm-4">Service</dt>
                <dd class="col-sm-8">#{{ $salonReview->e_service_id }}</dd>

                <dt class="col-sm-4">Créé le</dt>
                <dd class="col-sm-8">
                    <i class="bi bi-clock me-1"></i>{{ $salonReview->created_at->format('d/m/Y H:i') }}
                </dd>

                <dt class="col-sm-4">Mis à jour le</dt>
                <dd class="col-sm-8">
                    <i class="bi bi-clock-history me-1"></i>{{ $salonReview->updated_at->format('d/m/Y H:i') }}
                </dd>
            </dl>
        </div>
    </div>


