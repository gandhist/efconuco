<header class="main-header">
  <!-- Logo -->
  <a href="{{ url('') }}" class="logo">
    <!-- mini logo for sidebar mini 50x50 pixels -->
    <span class="logo-mini"><b>EIS</b></span>
    <!-- logo for regular state and mobile devices -->
    <span class="logo-lg"><b>E</b>fconuco</span>
  </a>
  <!-- Header Navbar: style can be found in header.less -->
  <nav class="navbar navbar-static-top">
    <!-- Sidebar toggle button-->
    <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
      <span class="sr-only">Toggle navigation</span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
    </a>

    <div class="navbar-custom-menu">
      <ul class="nav navbar-nav">
        <!-- User Account: style can be found in dropdown.less -->
        <li class="dropdown user user-menu">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <img src="{{ asset('AdminLTE-2.3.11/dist/img/avatar.png') }}" class="user-image" alt="User Image">
            <span class="hidden-xs">{{\Auth::user()->name}}</span>
          </a>
          <ul class="dropdown-menu">
            <!-- Menu Body -->
            <!-- Menu Footer-->
            <li class="user-footer">
              <div>
                <!-- <a href="#" class="btn btn-default btn-flat">Sign out</a> -->
                <form method="post" action="{{ url('logout') }}" style="display: inline">
                  {{ csrf_field() }}
                  <button class="btn btn-default" type="submit">Sign Out</button>
                </form>
              </div>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </nav>
</header>

<!-- =============================================== -->

<!-- Left side column. contains the sidebar -->
<aside class="main-sidebar">
  <!-- sidebar: style can be found in sidebar.less -->
  <section class="sidebar">
    <!-- Sidebar user panel -->
    <div class="user-panel">
      <div class="pull-left image">
        <img src="{{ asset('AdminLTE-2.3.11/dist/img/avatar.png') }}" class="img-circle" alt="User Image">
      </div>
      <div class="pull-left info">
        <p>{{\Auth::user()->name}}</p>
        <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
      </div>
    </div>

    <!-- sidebar menu: : style can be found in sidebar.less -->
    <ul class="sidebar-menu">
      <li class="header">MAIN NAVIGATION</li>
      <li class="treeview">
        <a href="{{ url('') }}">
          <i class="fa fa-dashboard"></i> <span>Dashboard</span>
        </a>
      </li>
      <li class="treeview">
        <a href="#">
          <i class="fa fa-database"></i> <span>Master</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
          <li class="">
            <a href="{{ url('master_stock') }}">
              <i class="fa fa-tasks"></i> <span>Barang & Jasa</span>
            </a>
          </li>
        </ul>
      </li>
      </li>

      </li>
      <li class="treeview">
        <a href="#">
          <i class="fa fa-shopping-cart"></i> <span>Pembelian</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
          <li class="">
            <a href="{{ url('purchase') }}">
              <i class="fa fa-exchange"></i> <span>Transaksi</span>
            </a>
          </li>
        </ul>
      </li>

    </li>
    <li class="treeview">
      <a href="#">
        <i class="fa fa-random"></i> <span>Penjualan</span>
        <span class="pull-right-container">
          <i class="fa fa-angle-left pull-right"></i>
        </span>
      </a>
      <ul class="treeview-menu">
        <li class="">
          <a href="{{ url('sales') }}">
            <i class="fa fa-exchange"></i> <span>Transaksi</span>
          </a>
        </li>
      </ul>
    </li>

      <!-- Kas kecil -->
      <li class="treeview">
        <a href="#">
          <i class="fa fa-money"></i> <span>Pengeluaran</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
          <li class="">
            <a href="{{ url('petty_cash/transaksi') }}">
              <i class="fa fa-exchange"></i> <span>Gaji</span>
            </a>
          </li>
          <li class="">
            <a href="{{ url('petty_cash/transaksi') }}">
              <i class="fa fa-exchange"></i> <span>Biaya Operasional</span>
            </a>
          </li>
        </ul>
      </li>
      <!-- end of kas kecil -->
            <!-- Kas kecil -->
            {{-- <li class="treeview">
              <a href="#">
                <i class="fa fa-money"></i> <span>Service Vehicle</span>
                <span class="pull-right-container">
                  <i class="fa fa-angle-left pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">
                <li class="">
                  <a href="{{ url('servicevehicle') }}">
                    <i class="fa fa-exchange"></i> <span>Service Vehicle</span>
                  </a>
                </li>
              </ul>
            </li> --}}

      <!-- Kas Stock Management -->
      <li class="treeview">
        <a href="#">
          <i class="fa fa-sliders"></i> <span>Inventory</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
          <li class="">
            <a href="{{ url('inventory') }}">
              <i class="fa fa-circle-o"></i> <span>Stock Information</span>
            </a>
          </li>
          <li class="">
            <a href="{{ url('mus') }}">
              <i class="fa fa-circle-o"></i> <span>Material Usage</span>
            </a>
          </li>
          {{-- <li class="">
            <a href="{{ url('stock/io_stock') }}">
              <i class="fa fa-circle-o"></i> <span>In/Out Stock</span>
            </a>
          </li>
          <li class="">
            <a href="{{ url('stock/tro_stock') }}">
              <i class="fa fa-circle-o"></i> <span>Stock Transfer</span>
            </a>
          </li>
          <li class="">
            <a href="{{ url('stock/purchase') }}">
              <i class="fa fa-circle-o"></i> <span>Purchasing</span>
            </a>
          </li> --}}
        </ul>
      </li>
      <!-- end of Stock Management -->

      <!-- Kas ATK Management -->
      {{-- <li class="treeview">
        <a href="#">
          <i class="fa fa-pencil-square-o"></i> <span>ATK Management</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
          <li class="">
            <a href="{{ url('atk/info') }}">
              <i class="fa fa-circle-o"></i> <span>ATK Stock Information</span>
            </a>
          </li>
          <li class="">
            <a href="{{ url('atk/restock') }}">
              <i class="fa fa-circle-o"></i> <span>Re-Stock ATK</span>
            </a>
          </li>
          <li class="">
            <a href="{{ url('atk/io_stock') }}">
              <i class="fa fa-circle-o"></i> <span>In/Out Stock ATK</span>
            </a>
          </li>
          <li class="">
            <a href="{{ url('atk/tro_stock') }}">
              <i class="fa fa-circle-o"></i> <span>Stock Transfer ATK</span>
            </a>
          </li>
          <li class="">
            <a href="{{ url('atk/purchase') }}">
              <i class="fa fa-circle-o"></i> <span>Purchase ATK</span>
            </a>
          </li>
        </ul>
      </li> --}}
      <!-- end of ATK Management -->

      <!-- Laporan -->
      <li class="treeview">
        <a href="#">
          <i class="fa fa-database"></i> <span>Laporan</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
          <li class="">
            <a href="{{ url('report/lembur') }}">
              <i class="fa fa-tasks"></i> <span>Pembelian PPN</span>
            </a>
          </li>
          <li class="">
            <a href="{{ url('report/rekap/beban' ) }}">
              <i class="fa fa-tasks"></i> <span>Pembelian Langsung</span>
            </a>
          </li>
          <li class="">
            <a href="{{ url('security') }}">
              <i class="fa fa-tasks"></i> <span>Penjualan</span>
            </a>
          </li>
          <li class="">
            <a href="{{ url('rekapkehadiran') }}">
              <i class="fa fa-tasks"></i> <span>Persediaan Barang</span>
            </a>
          </li>
        </ul>
      </li>
      <!-- End Of Laporan -->

      @if(Auth::user()->role_id == 1
      || Auth::user()->role_id == 2
      || Auth::user()->role_id == 3
      || Auth::user()->role_id == 4
      || Auth::user()->role_id == 5)

      @endif


      </li>

      @if(Auth::user()->role_id==1)

      <li class="treeview {{Request::is('user*') ? 'active' : ''}}">
        <a href="#">
          <i class="fa fa-users"></i> <span>Kelola Admin</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
          <li class="{{Request::is('users') ? 'active' : ''}}">
            <a href="{{ url('users') }}">
              <i class="fa fa-user"></i> <span>Admin</span>
            </a>
          </li>
          <li class="{{Request::is('user_role') ? 'active' : ''}}">
            <a href="{{ url('user_role') }}">
              <i class="fa fa-user"></i> <span>Role</span>
            </a>
          </li>
        </ul>
      </li>
      @endif

      {{-- <li class="treeview {{Request::is('tipetransaksi*') ? 'active' : ''}}">
        <a href="{{ url('tipetransaksi') }}">
          <i class="fa fa-database"></i> <span>Tipe Transaksi</span>
        </a>
      </li>

      <li class="treeview {{Request::is('metodepembayaran*') ? 'active' : ''}}">
        <a href="{{ url('metodepembayaran') }}">
          <i class="fa fa-database"></i> <span>Metode Pembayaran</span>
        </a>
      </li> --}}

    </ul>
  </section>
  <!-- /.sidebar -->
</aside>