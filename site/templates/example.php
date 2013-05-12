<?php if (path('section')) go(url('404')) ?>
<?php render(ui.'snippets/header', array(
  'title'       => 'Sample page',
  'description' => ''
  )) ?>



  <h1>Sample page</h1>
  <a href="<?php echo root() ?>">Go back to the home page</a>



<?php render('snippets/footer') ?>