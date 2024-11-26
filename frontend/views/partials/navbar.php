<ul>
   <li>
      <a href="/">Home</a>
   </li>
   <li>
      <a href="/threads">Threads</a>
   </li>
   <li>
      <a href="/signup">Sign Up</a>
   </li>
   <li>
      <a href="/signin">Sign In</a>
   </li>
   <li>
      <button type="button" id="logout">Sign out</button>
   </li>
   <?php if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/thread'): ?>
      <li>
         <form action="" method="post">
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit">Delete</button>
         </form>
      </li>
   <?php endif ?>
</ul>