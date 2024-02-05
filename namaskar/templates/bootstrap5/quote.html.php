<figure class="text-start h1">
    <blockquote class="blockquote ">
        <p class="h1"><?= $this->getValue('content', $content ?? null) ?></p>
    </blockquote>
    <figcaption class="blockquote-footer h2">
        <?php if($this->getValue('author', $author ?? null)): ?> <?= $this->getValue('author', $author ?? null)  ?>, <?php endif; ?>
        <?php if($this->getValue('source', $source ?? null)): ?> <cite title="Source Title"><?= $this->getValue('source', $source ?? null)  ?></cite> <?php endif; ?>

    </figcaption>
</figure>