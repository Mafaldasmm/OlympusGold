<?php

declare(strict_types=1);

?>
<div id="site-confirm" class="site-confirm" hidden aria-hidden="true">
    <div class="site-confirm__backdrop" data-confirm-dismiss></div>
    <div class="site-confirm__dialog" role="dialog" aria-modal="true" aria-labelledby="site-confirm-title">
        <h2 id="site-confirm-title" class="site-confirm__title">Confirmar</h2>
        <p id="site-confirm-message" class="site-confirm__message"></p>
        <div class="site-confirm__actions">
            <button type="button" class="site-confirm__btn site-confirm__btn--cancel" data-confirm-cancel>
                Cancelar
            </button>
            <button type="button" class="site-confirm__btn site-confirm__btn--ok" data-confirm-ok>
                Confirmar
            </button>
        </div>
    </div>
</div>
