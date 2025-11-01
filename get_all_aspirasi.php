<?php
include "koneksi.php";
session_start();

$query = "SELECT * FROM aspirasi ORDER BY tanggal DESC";
$res = $koneksi->query($query);
$i = 1;

while ($row = $res->fetch_assoc()):
?>
<tr>
  <td><?= $i++ ?></td>
  <td><?= htmlspecialchars($row['nama']) ?></td>
  <td><?= htmlspecialchars($row['nim']) ?></td>
  <td><?= htmlspecialchars($row['jurusan']) ?></td>
  <td><?= htmlspecialchars($row['kategori']) ?></td>
  <td style="max-width:320px;white-space:pre-wrap;"><?= nl2br(htmlspecialchars($row['isi_aspirasi'])) ?></td>
  <td>
    <?php
    $badge = 'secondary';
    if ($row['status'] === 'Menunggu') $badge = 'warning';
    if ($row['status'] === 'Diproses') $badge = 'info';
    if ($row['status'] === 'Selesai') $badge = 'success';
    ?>
    <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($row['status']) ?></span>
  </td>
  <td><?= htmlspecialchars($row['tanggal']) ?></td>
  <?php if ($_SESSION['role'] === 'admin'): ?>
    <td>
      <form method="post" action="update_status.php" class="d-flex gap-1">
        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
        <select name="status" class="form-select form-select-sm" style="width:150px;">
          <option value="Menunggu" <?= $row['status']=='Menunggu'?'selected':'' ?>>Menunggu</option>
          <option value="Diproses" <?= $row['status']=='Diproses'?'selected':'' ?>>Diproses</option>
          <option value="Selesai" <?= $row['status']=='Selesai'?'selected':'' ?>>Selesai</option>
        </select>
        <button class="btn btn-sm btn-primary">Ubah</button>
      </form>
    </td>
  <?php endif; ?>
</tr>
<?php endwhile; ?>
