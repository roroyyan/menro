<?php
if (!isset($_SESSION['username'])) {
  echo '<div class="alert alert-warning text-center">Silakan login terlebih dahulu.</div>';
  return;
}

$curMonth = date('n');
$curYear = date('Y');
?>

<div class="card border-0 shadow-sm" style="background: rgba(255,255,255,0.9); border-radius: 15px;">
  <div class="card-body">
    <h5 class="fw-bold text-primary mb-4">
      <i class="bi bi-pie-chart-fill me-2"></i> Statistik Aspirasi Bulanan
    </h5>

    <div class="row align-items-end mb-4">
      <div class="col-md-4 mb-2">
        <label class="form-label fw-semibold text-secondary">Pilih Bulan</label>
        <select id="monthSelect" class="form-select shadow-sm border-primary-subtle">
          <?php for($m=1;$m<=12;$m++):
            $label = DateTime::createFromFormat('!m',$m)->format('F'); ?>
            <option value="<?= $m ?>" <?= $m==$curMonth?'selected':'' ?>><?= $label ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-3 mb-2">
        <label class="form-label fw-semibold text-secondary">Pilih Tahun</label>
        <select id="yearSelect" class="form-select shadow-sm border-primary-subtle">
          <?php $start=(int)date('Y')-3; $end=(int)date('Y')+1;
          for($y=$start;$y<=$end;$y++): ?>
            <option value="<?= $y ?>" <?= $y==$curYear?'selected':'' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-3 mb-2">
        <button id="btnLoad" class="btn btn-primary shadow-sm">
          <i class="bi bi-arrow-repeat me-1"></i> Tampilkan
        </button>
      </div>
    </div>

    <!-- Grafik kategori -->
    <div class="row">
      <div class="col-lg-6 mb-4">
        <canvas id="pieChartKategori" style="max-height:400px;"></canvas>
      </div>
      <div class="col-lg-6">
        <div id="reportSummary" class="border rounded p-3 shadow-sm bg-light-subtle">
          <div class="text-center text-muted">
            <i class="bi bi-info-circle me-1"></i> Pilih bulan & tahun untuk menampilkan grafik
          </div>
        </div>
      </div>
    </div>

    <!-- 🔹 Grafik status -->
    <hr class="my-4">
    <h6 class="fw-bold text-primary mb-3"><i class="bi bi-graph-up-arrow me-2"></i>Status Laporan Bulanan</h6>
    <div class="row">
      <div class="col-lg-6 mb-4">
        <canvas id="pieChartStatus" style="max-height:400px;"></canvas>
      </div>
      <div class="col-lg-6">
        <div id="statusSummary" class="border rounded p-3 shadow-sm bg-light-subtle text-muted text-center">
          <i class="bi bi-info-circle me-1"></i> Statistik status laporan akan tampil di sini.
        </div>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const monthSel=document.getElementById('monthSelect');
  const yearSel=document.getElementById('yearSelect');
  const btnLoad=document.getElementById('btnLoad');
  const ctxCat=document.getElementById('pieChartKategori').getContext('2d');
  const ctxStat=document.getElementById('pieChartStatus').getContext('2d');
  let chartCat=null, chartStat=null;

  async function fetchData(month,year){
    const res=await fetch(`get_report_data.php?month=${month}&year=${year}`);
    return res.json();
  }

  function renderKategori(data){
    const labels=data.categories.map(c=>c.kategori+' ('+c.total+')');
    const values=data.categories.map(c=>c.total);
    const colors=['#004c97','#6ec6ff','#7fc97f','#fdc086','#beaed4'];

    if(chartCat) chartCat.destroy();
    chartCat=new Chart(ctxCat,{
      type:'pie',
      data:{labels:labels,datasets:[{data:values,backgroundColor:colors,borderWidth:2,borderColor:'#fff'}]},
      options:{plugins:{legend:{position:'bottom'}}}
    });

    let html=`<h6 class='fw-bold text-primary mb-3'>Ringkasan (${data.total} laporan)</h6><ul class='list-group'>`;
    data.categories.forEach(c=>{
      html+=`<li class='list-group-item d-flex justify-content-between align-items-center'>
        ${c.kategori}<span class='badge bg-primary rounded-pill'>
        ${c.total} (${c.percent.toFixed(1)}%)</span></li>`;
    });
    html+='</ul>';
    document.getElementById('reportSummary').innerHTML=html;
  }

  function renderStatus(data){
    const labels=data.statusSummary.map(s=>s.status+' ('+s.total+')');
    const values=data.statusSummary.map(s=>s.total);
    const colors=['#f1c40f','#3498db','#2ecc71']; // kuning, biru, hijau

    if(chartStat) chartStat.destroy();
    chartStat=new Chart(ctxStat,{
      type:'pie',
      data:{labels:labels,datasets:[{data:values,backgroundColor:colors,borderWidth:2,borderColor:'#fff'}]},
      options:{plugins:{legend:{position:'bottom'}}}
    });

    let html=`<h6 class='fw-bold text-primary mb-3'>Status (${data.total} laporan)</h6><ul class='list-group'>`;
    data.statusSummary.forEach(s=>{
      html+=`<li class='list-group-item d-flex justify-content-between align-items-center'>
        ${s.status}<span class='badge bg-primary rounded-pill'>
        ${s.total} (${s.percent.toFixed(1)}%)</span></li>`;
    });
    html+='</ul>';
    document.getElementById('statusSummary').innerHTML=html;
  }

  async function loadAll(){
    const month=monthSel.value, year=yearSel.value;
    btnLoad.disabled=true; 
    btnLoad.innerHTML='<span class="spinner-border spinner-border-sm"></span> Loading...';
    
    const res=await fetchData(month,year);
    
    btnLoad.disabled=false; 
    btnLoad.innerHTML='<i class="bi bi-arrow-repeat me-1"></i> Tampilkan';
    
    if(res.success){
      const hasKategori = res.categories.length > 0;
      const hasStatus = res.statusSummary.length > 0;

      // ✅ jika ada data kategori
      if(hasKategori){
        renderKategori(res);
      } else {
        if(chartCat) chartCat.destroy();
        document.getElementById('reportSummary').innerHTML = `
          <div class="alert alert-warning text-center">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Tidak ada data aspirasi untuk bulan ini.
          </div>`;
      }

      // ✅ jika ada data status
      if(hasStatus){
        renderStatus(res);
      } else {
        if(chartStat) chartStat.destroy();
        document.getElementById('statusSummary').innerHTML = `
          <div class="alert alert-warning text-center">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Tidak ada data status laporan untuk bulan ini.
          </div>`;
      }
    } else {
      document.getElementById('reportSummary').innerHTML = `
        <div class="alert alert-danger text-center">
          <i class="bi bi-x-circle me-2"></i>
          Gagal memuat data laporan.
        </div>`;
      document.getElementById('statusSummary').innerHTML = '';
    }
  }

  btnLoad.addEventListener('click',loadAll);
  loadAll();
});
</script>

