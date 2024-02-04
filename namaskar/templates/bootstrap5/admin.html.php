<?php if($this->getValue('page.admin.page', $page ?? null) === 'LOGIN'): ?>

<div class="row d-flex justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card bg-white">
            <div class="card-body p-5">
                <form class="mb-3 mt-md-1" method="post">
                    <h2 class="fw-bold mb-0 text-uppercase mt-0">Namaskar Admin</h2>
                    <p class=" mb-5">Please enter your login and password! <br><strong><?= $this->getValue('page.warning', $page ?? null)  ?></strong></p>
                    <p class=" mb-0"></p>
                    <div class="mb-1">
                        <label for="email" class="form-label ">Email address</label>
                        <input type="text" class="form-control" id="email" name="email" placeholder="name@example.com">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label ">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="*******">
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-outline-dark" type="submit">Login</button>
                    </div>
                </form>


            </div>
        </div>
    </div>
</div>



<?php endif; ?>

<?php if($this->getValue('page.admin.page', $page ?? null) === 'ADMIN'): ?>
SITES
<?php endif; ?>


<?php if($this->getValue('page.admin.page', $page ?? null) === 'EDIT_CONFIG_FILE'): ?>
<form action="<?= $this->getValue('admin.file', $admin ?? null)  ?>" method="post">
    <fieldset>
        <legend>Edition des fichiers de configuration</legend>
        <div class="mb-3">
            <label for="file" class="form-label"><?= $this->getValue('admin.site', $admin ?? null) . '/' . $this->getValue('admin.file', $admin ?? null) ?></label>
            <textarea id="file" name="file" rows="20" cols="80" class="form-control">$fcontent</textarea>
        </div>
        <input type="hidden" name="token" value="$md5">
        <input type="hidden" name="file" value="<?= $this->getValue('admin.file', $admin ?? null)  ?>">
        <button type="submit" class="btn btn-primary">Save</button>
    </fieldset>
</form>

<?php endif; ?>