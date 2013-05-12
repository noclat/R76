<?php if (path('section')) go(url('404')) ?>
<?php render(ui.'snippets/header', array(
  'title'       => 'Home',
  'description' => ''
  )) ?>
  
  
  
  <h1>Home</h1>
  <a href="<?php echo url('example') ?>">Sample page</a>
  <a href="<?php echo url('error') ?>">Unexisting page</a>
  
  

<?php include ui.'snippets/footer.php' ?>