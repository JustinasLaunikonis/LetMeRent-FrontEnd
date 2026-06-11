<div class="section-title">Location & Details</div>
<div class="map-box">
  <svg viewBox="0 0 680 186" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
    <rect width="680" height="186" fill="#E8F4FD"/>
    <rect x="18" y="12" width="90" height="58" fill="white" opacity="0.7" rx="4"/>
    <rect x="128" y="18" width="68" height="48" fill="white" opacity="0.6" rx="3"/>
    <rect x="216" y="8" width="98" height="64" fill="white" opacity="0.7" rx="4"/>
    <rect x="334" y="16" width="76" height="54" fill="white" opacity="0.6" rx="3"/>
    <rect x="430" y="12" width="92" height="58" fill="white" opacity="0.7" rx="4"/>
    <rect x="18" y="108" width="96" height="58" fill="white" opacity="0.6" rx="4"/>
    <rect x="134" y="118" width="82" height="52" fill="white" opacity="0.7" rx="3"/>
    <rect x="236" y="112" width="88" height="58" fill="white" opacity="0.6" rx="4"/>
    <rect x="346" y="118" width="92" height="52" fill="white" opacity="0.7" rx="3"/>
    <rect x="460" y="108" width="76" height="58" fill="white" opacity="0.6" rx="4"/>
    <rect x="0" y="84" width="680" height="14" fill="white" opacity="0.85"/>
    <rect x="316" y="0" width="14" height="186" fill="white" opacity="0.85"/>

    <g transform="translate(323,42)">
      <rect x="-26" y="-30" width="52" height="24" rx="12" fill="#1558A7"/>
      <text x="0" y="-13" text-anchor="middle" font-size="10" font-family="sans-serif" fill="white" font-weight="bold"><?php echo esc($campusLabel); ?></text>
      <polygon points="0,0 -6,-8 6,-8" fill="#1558A7"/>
    </g>

    <g transform="translate(196,124)">
      <rect x="-24" y="-30" width="48" height="24" rx="12" fill="#059669"/>
      <text x="0" y="-13" text-anchor="middle" font-size="10" font-family="sans-serif" fill="white" font-weight="bold"><?php echo esc($travelLabel); ?></text>
      <polygon points="0,0 -6,-8 6,-8" fill="#059669"/>
    </g>

    <path d="M196 112 Q260 92 323 68" stroke="#1558A7" stroke-width="2.5" stroke-dasharray="6,5" fill="none" opacity="0.7"/>
    <text x="230" y="78" font-size="9" fill="#1558A7" font-family="sans-serif" font-weight="600" opacity="0.9"><?php echo esc($mapLocation); ?></text>
  </svg>
</div>

<table class="commute-table">
  <?php foreach ($commuteRows as $row): ?>
    <tr>
      <td><?php echo esc($row['label']); ?></td>
      <td><?php echo esc($row['value']); ?></td>
    </tr>
  <?php endforeach; ?>
</table>
