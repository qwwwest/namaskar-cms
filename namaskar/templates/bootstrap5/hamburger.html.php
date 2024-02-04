<?php if($this->getValue('theme.navbar.hamburger.animated', $theme ?? null)): ?>
<button class="navbar-toggler collapsed animated-hamburger" type="button" data-bs-toggle="collapse"
   data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
   <span></span>
   <span></span>
   <span></span>
</button>

<?php else: ?>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse"
   aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
   <span class="navbar-toggler-icon"></span>
</button>


<?php endif; ?>