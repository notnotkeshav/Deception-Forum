<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
   <div class="container">
      <!-- Navbar Brand -->
      <a class="navbar-brand fw-bold text-primary" href="/">Forum</a>
      <!-- Toggler for Mobile View -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
         <span class="navbar-toggler-icon"></span>
      </button>
      <!-- Navbar Links -->
      <div class="collapse navbar-collapse" id="navbarNav">
         <ul class="navbar-nav ms-auto">
            <!-- General Links -->
            <li class="nav-item">
               <a class="nav-link" href="/threads">Threads</a>
            </li>
            <?php if (!empty($_SESSION) && $_SESSION['user']['accessLevel'] >= 5): ?>
               <li class="nav-item">
                  <a class="nav-link" href="/generate_invite_code">Generate Invite Code</a>
               </li>
            <?php endif; ?>
            <li class="nav-item">
               <a class="nav-link" href="/notifications">Notifications</a>
            </li>
            <li class="nav-item">
               <a class="nav-link" href="/private-chats">Private Chats</a>
            </li>
            <li class="nav-item">
               <a class="nav-link" href="/group-chats">Group Chats</a>
            </li>
            <!-- Authentication Links -->
            <?php if (!isset($_SESSION['userId'])): ?>
               <li class="nav-item mt-1">
                  <a class="btn btn-outline-secondary btn-sm ms-3 px-4 fw-bold" href="/signin">Sign In</a>
               </li>
               <li class="nav-item mt-1">
                  <a class="btn btn-primary btn-sm ms-2 px-4 fw-bold text-white" href="/signup">Sign Up</a>
               </li>
            <?php else: ?>
               <li class="nav-item">
                  <a class="nav-link" href="/user">User Profile</a>
               </li>
               <li class="nav-item">
                  <div class="d-inline">
                     <button type="submit" id="logout" class="btn btn-danger btn-sm ms-2 px-4">Sign Out</button>
                  </div>
               </li>
            <?php endif; ?>
         </ul>
      </div>
   </div>
</nav>