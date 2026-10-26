import React, { useState, useCallback, useRef, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import {
  ReactFlow,
  MiniMap,
  Controls,
  Background,
  addEdge,
  useNodesState,
  useEdgesState,
  Handle,
  Position,
  MarkerType,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import '../css/app.css';

// ============================================================================
// DAFTAR STUDI KASUS & SOAL LATIHAN SISWA (DIPEROLEH DINAMIS DARI DATABASE GURU)
// ============================================================================
const defaultCaseStudies = {
  luas_segitiga: {
    id: 'luas_segitiga',
    title: 'Studi Kasus 1: Menghitung Luas Segitiga',
    icon: '📐',
    badge: 'Alur Sekuensial',
    formula: 'Rumus: Luas = 0.5 × Alas × Tinggi',
    description: 'Buatlah flowchart yang membaca masukan nilai alas dan tinggi dari pengguna, menghitung luas segitiga dengan rumus matematika, lalu mencetak nilai hasil luas ke layar.',
    flow_type: 'sequential',
  },
  kelulusan: {
    id: 'kelulusan',
    title: 'Studi Kasus 2: Cek Kelulusan Nilai Ujian',
    icon: '🎓',
    badge: 'Alur Percabangan',
    formula: 'Syarat: Nilai ≥ 75 → LULUS | Nilai < 75 → REMEDIAL',
    description: 'Rancang flowchart dengan: 1) Mulai, 2) Input Nilai Ujian, 3) Keputusan Nilai >= 75? (cabang Ya & Tidak), 4) Output Cetak LULUS & REMEDIAL, 5) Selesai.',
    flow_type: 'branching',
    expected_decision: 'Nilai >= 75?',
  },
  bebas: {
    id: 'bebas',
    title: 'Studi Kasus Mandiri: Eksplorasi Bebas',
    icon: '✏️',
    badge: 'Proyek Bebas',
    formula: 'Alur Logika Bebas Sesuai Instruksi Guru',
    description: 'Rancang flowchart secara mandiri sesuai dengan tugas atau studi kasus pemrograman yang diberikan oleh guru pada pertemuan hari ini.',
    flow_type: 'sequential',
  },
};

const getCaseStudiesMap = () => {
  const dynamicList = typeof window !== 'undefined' ? window.__FLOWCHART_CASE_STUDIES__ : null;
  if (!Array.isArray(dynamicList) || dynamicList.length === 0) {
    return defaultCaseStudies;
  }

  const map = {};
  dynamicList.forEach((c) => {
    map[c.case_key] = {
      id: c.case_key,
      title: c.title,
      icon: c.icon || (c.flow_type === 'branching' ? '🔀' : '📐'),
      badge: c.badge || (c.flow_type === 'branching' ? 'Alur Percabangan' : 'Alur Sekuensial'),
      formula: c.formula || '',
      description: c.description || '',
      flow_type: c.flow_type || 'sequential',
      expected_inputs: c.expected_inputs || '',
      expected_process: c.expected_process || '',
      expected_decision: c.expected_decision || '',
      expected_yes_output: c.expected_yes_output || '',
      expected_no_output: c.expected_no_output || '',
      expected_outputs: c.expected_outputs || '',
      reference_flow: c.reference_flow || null,
    };
  });
  return Object.keys(map).length > 0 ? map : defaultCaseStudies;
};

// ============================================================================
// CUSTOM NODE COMPONENTS DENGAN PENGATURAN WARNA MODERN & RAMAH MATA
// ============================================================================

// 1. TERMINATOR (Mulai / Selesai) - Aksen Kuning/Amber Lembut
const TerminatorNode = ({ data, id }) => {
  return (
    <div className="group relative transition-all duration-200">
      <Handle
        type="target"
        position={Position.Top}
        className="!w-3.5 !h-3.5 sm:!w-3 sm:!h-3 !bg-amber-500 !border-2 !border-white !shadow-sm"
      />
      <div className="bg-amber-50 border-2 border-amber-400 text-amber-950 rounded-full px-5 py-2.5 min-w-[150px] text-center shadow-sm hover:shadow-md transition-all flex flex-col items-center">
        <div className="flex items-center gap-1.5 text-[10px] font-extrabold text-amber-700 tracking-wider uppercase">
          <span className="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
          <span>Terminator</span>
        </div>
        <input
          type="text"
          value={data.label || ''}
          onChange={(e) => data.onChangeLabel?.(id, e.target.value)}
          placeholder="Mulai / Selesai"
          className="w-full text-center bg-transparent border-b border-transparent hover:border-amber-300 focus:border-amber-500 font-bold text-sm text-amber-950 outline-none px-1 py-0.5 mt-0.5"
        />
      </div>
      <Handle
        type="source"
        position={Position.Bottom}
        className="!w-3.5 !h-3.5 sm:!w-3 sm:!h-3 !bg-amber-500 !border-2 !border-white !shadow-sm"
      />
      <button
        type="button"
        onClick={() => data.onDeleteNode?.(id)}
        className="absolute -top-2 -right-2 w-5 h-5 bg-rose-500 hover:bg-rose-600 text-white rounded-full text-xs font-bold items-center justify-center shadow-md flex sm:hidden group-hover:flex transition-transform hover:scale-110 z-10"
        title="Hapus Simbol"
      >
        ×
      </button>
    </div>
  );
};

// 2. INPUT / OUTPUT (Jajar Genjang) - Aksen Biru Langit (Sky) Lembut
const IONode = ({ data, id }) => {
  return (
    <div className="group relative transition-all duration-200">
      <Handle
        type="target"
        position={Position.Top}
        className="!w-3.5 !h-3.5 sm:!w-3 sm:!h-3 !bg-sky-500 !border-2 !border-white !shadow-sm"
      />
      <div className="bg-sky-50 border-2 border-sky-400 text-sky-950 px-6 py-3 min-w-[170px] text-center shadow-sm hover:shadow-md transition-all transform -skew-x-12 rounded-lg">
        <div className="transform skew-x-12 flex flex-col items-center">
          <div className="flex items-center gap-1.5 text-[10px] font-extrabold text-sky-700 tracking-wider uppercase">
            <span className="w-2 h-2 bg-sky-500 inline-block rounded-xs"></span>
            <span>Input / Output</span>
          </div>
          <input
            type="text"
            value={data.label || ''}
            onChange={(e) => data.onChangeLabel?.(id, e.target.value)}
            placeholder="Baca / Cetak Data"
            className="w-full text-center bg-transparent border-b border-transparent hover:border-sky-300 focus:border-sky-500 font-bold text-sm text-sky-950 outline-none px-1 py-0.5 mt-0.5"
          />
        </div>
      </div>
      <Handle
        type="source"
        position={Position.Bottom}
        className="!w-3.5 !h-3.5 sm:!w-3 sm:!h-3 !bg-sky-500 !border-2 !border-white !shadow-sm"
      />
      <button
        type="button"
        onClick={() => data.onDeleteNode?.(id)}
        className="absolute -top-2 -right-2 w-5 h-5 bg-rose-500 hover:bg-rose-600 text-white rounded-full text-xs font-bold items-center justify-center shadow-md flex sm:hidden group-hover:flex transition-transform hover:scale-110 z-10"
        title="Hapus Simbol"
      >
        ×
      </button>
    </div>
  );
};

// 3. PROSES (Persegi Panjang) - Aksen Hijau Emerald Lembut
const ProcessNode = ({ data, id }) => {
  return (
    <div className="group relative transition-all duration-200">
      <Handle
        type="target"
        position={Position.Top}
        className="!w-3.5 !h-3.5 sm:!w-3 sm:!h-3 !bg-emerald-500 !border-2 !border-white !shadow-sm"
      />
      <div className="bg-emerald-50 border-2 border-emerald-400 text-emerald-950 rounded-xl px-5 py-3 min-w-[170px] text-center shadow-sm hover:shadow-md transition-all flex flex-col items-center">
        <div className="flex items-center gap-1.5 text-[10px] font-extrabold text-emerald-700 tracking-wider uppercase">
          <span className="w-2 h-2 bg-emerald-500 inline-block rounded-xs"></span>
          <span>Proses (Langkah)</span>
        </div>
        <input
          type="text"
          value={data.label || ''}
          onChange={(e) => data.onChangeLabel?.(id, e.target.value)}
          placeholder="Langkah Perhitungan..."
          className="w-full text-center bg-transparent border-b border-transparent hover:border-emerald-300 focus:border-emerald-500 font-bold text-sm text-emerald-950 outline-none px-1 py-0.5 mt-0.5"
        />
      </div>
      <Handle
        type="source"
        position={Position.Bottom}
        className="!w-3.5 !h-3.5 sm:!w-3 sm:!h-3 !bg-emerald-500 !border-2 !border-white !shadow-sm"
      />
      <button
        type="button"
        onClick={() => data.onDeleteNode?.(id)}
        className="absolute -top-2 -right-2 w-5 h-5 bg-rose-500 hover:bg-rose-600 text-white rounded-full text-xs font-bold items-center justify-center shadow-md flex sm:hidden group-hover:flex transition-transform hover:scale-110 z-10"
        title="Hapus Simbol"
      >
        ×
      </button>
    </div>
  );
};

// 4. KEPUTUSAN / PERCABANGAN (Belah Ketupat / Diamond) - Aksen Oranye Lembut
const DecisionNode = ({ data, id }) => {
  return (
    <div className="group relative w-36 h-36 flex items-center justify-center">
      <Handle
        type="target"
        position={Position.Top}
        className="!w-3.5 !h-3.5 sm:!w-3 sm:!h-3 !bg-orange-500 !border-2 !border-white !shadow-sm !top-1"
        title="Titik Masuk Alur"
      />
      <div className="w-28 h-28 bg-orange-50 border-2 border-orange-400 text-orange-950 transform rotate-45 rounded-lg shadow-sm hover:shadow-md transition-all flex items-center justify-center" />

      <div className="absolute inset-0 flex flex-col items-center justify-center px-4 pointer-events-auto">
        <span className="text-[9px] font-extrabold text-orange-700 uppercase tracking-tight flex items-center gap-1">
          <span className="w-1.5 h-1.5 bg-orange-500 inline-block rotate-45"></span>
          <span>Keputusan</span>
        </span>
        <input
          type="text"
          value={data.label || ''}
          onChange={(e) => data.onChangeLabel?.(id, e.target.value)}
          placeholder="Nilai >= 75 ?"
          className="w-24 text-center bg-transparent border-b border-transparent hover:border-orange-300 focus:border-orange-500 font-bold text-xs text-orange-950 outline-none py-0.5 mt-0.5"
        />
      </div>

      {/* Titik & Label Cabang Kiri: YA (Hijau) */}
      <div className="absolute -left-1.5 top-1/2 -translate-y-6 pointer-events-none z-10">
        <span className="text-[9px] font-black bg-emerald-600 text-white px-1.5 py-0.5 rounded-sm shadow-xs tracking-tight">
          Ya
        </span>
      </div>
      <Handle
        type="source"
        position={Position.Left}
        id="left"
        className="!w-4 !h-4 sm:!w-3.5 sm:!h-3.5 !bg-emerald-500 !border-2 !border-white !shadow-md !left-0.5 hover:scale-125 transition-transform"
        title="Cabang YA (Kiri)"
      />

      {/* Titik & Label Cabang Kanan: TIDAK (Merah) */}
      <div className="absolute -right-3 top-1/2 -translate-y-6 pointer-events-none z-10">
        <span className="text-[9px] font-black bg-rose-600 text-white px-1.5 py-0.5 rounded-sm shadow-xs tracking-tight">
          Tidak
        </span>
      </div>
      <Handle
        type="source"
        position={Position.Right}
        id="right"
        className="!w-4 !h-4 sm:!w-3.5 sm:!h-3.5 !bg-rose-500 !border-2 !border-white !shadow-md !right-0.5 hover:scale-125 transition-transform"
        title="Cabang TIDAK (Kanan)"
      />

      <Handle
        type="source"
        position={Position.Bottom}
        id="bottom"
        className="!w-3.5 !h-3.5 sm:!w-3 sm:!h-3 !bg-orange-500 !border-2 !border-white !shadow-sm !bottom-1"
        title="Cabang Bawah"
      />

      <button
        type="button"
        onClick={() => data.onDeleteNode?.(id)}
        className="absolute top-1 right-1 w-5 h-5 bg-rose-500 hover:bg-rose-600 text-white rounded-full text-xs font-bold items-center justify-center shadow-md flex sm:hidden group-hover:flex transition-transform hover:scale-110 z-10"
        title="Hapus Simbol"
      >
        ×
      </button>
    </div>
  );
};

const nodeTypes = {
  terminator: TerminatorNode,
  io: IONode,
  process: ProcessNode,
  decision: DecisionNode,
};

// ============================================================================
// MESIN EVALUASI CLIENT-SIDE (FALLBACK OFFLINE & GITHUB PAGES STATIS)
// Menjamin aplikasi tetap berjalan 100% dan menilai diagram bahkan saat server Laravel mati
// ============================================================================
export function evaluateDiagramLocally(nodes, edges, caseKey, currentCase = {}) {
  let score = 0;
  const feedback = [];

  const terminatorNodes = nodes.filter((n) => n.type === 'terminator');
  const ioNodes = nodes.filter((n) => n.type === 'io');
  const processNodes = nodes.filter((n) => n.type === 'process');
  const decisionNodes = nodes.filter((n) => n.type === 'decision');

  const cleanLabel = (n) => (n.data?.label || '').toLowerCase().trim();

  // 1. EVALUASI TERMINATOR (Mulai & Selesai) - 20 Poin
  let hasStart = false;
  let hasEnd = false;

  terminatorNodes.forEach((node) => {
    const lbl = cleanLabel(node);
    if (lbl.includes('mulai') || lbl.includes('start') || lbl.includes('begin')) {
      hasStart = true;
    } else if (lbl.includes('selesai') || lbl.includes('end') || lbl.includes('stop') || lbl.includes('tamat')) {
      hasEnd = true;
    }
  });

  if (hasStart) {
    score += 10;
    feedback.push('✅ Simbol Mulai (Terminator) dengan teks "Mulai" sudah tepat.');
  } else {
    feedback.push('⚠️ Belum ada simbol Terminator dengan teks "Mulai".');
  }

  if (hasEnd) {
    score += 10;
    feedback.push('✅ Simbol Selesai (Terminator) dengan teks "Selesai" sudah tepat.');
  } else {
    feedback.push('⚠️ Belum ada simbol Terminator dengan teks "Selesai".');
  }

  // 2. KESESUAIAN TEKS SESUAI STUDI KASUS
  if (caseKey === 'luas_segitiga') {
    // A. Teks Input (15 Poin)
    let hasAlas = false;
    let hasTinggi = false;

    ioNodes.forEach((node) => {
      const lbl = cleanLabel(node);
      if (lbl.includes('alas')) hasAlas = true;
      if (lbl.includes('tinggi')) hasTinggi = true;
    });

    if (hasAlas && hasTinggi) {
      score += 15;
      feedback.push('✅ Teks Input tepat: Terdeteksi masukan data alas dan tinggi.');
    } else if (hasAlas || hasTinggi) {
      score += 8;
      feedback.push('⚠️ Teks Input belum lengkap: Hanya terdeteksi ' + (hasAlas ? 'alas' : 'tinggi') + '. Masukkan alas dan tinggi.');
    } else if (ioNodes.length > 0) {
      score += 5;
      feedback.push('⚠️ Teks Input masih default. Tuliskan "Input Alas, Tinggi".');
    } else {
      feedback.push('⚠️ Tambahkan simbol Input/Output (Jajar Genjang) dengan teks "Input Alas, Tinggi".');
    }

    // B. Teks Proses Rumus Luas (25 Poin)
    let hasValidFormula = false;
    let hasPartialFormula = false;

    processNodes.forEach((node) => {
      const lbl = cleanLabel(node);
      if (
        (lbl.includes('0.5') || lbl.includes('1/2')) &&
        lbl.includes('alas') &&
        lbl.includes('tinggi')
      ) {
        hasValidFormula = true;
      } else if (lbl.includes('alas') && lbl.includes('tinggi') && lbl.includes('/')) {
        hasValidFormula = true;
      } else if (lbl.includes('luas') || lbl.includes('alas') || lbl.includes('tinggi')) {
        hasPartialFormula = true;
      }
    });

    if (hasValidFormula) {
      score += 25;
      feedback.push('✅ Teks Proses tepat: Rumus perhitungan luas (Luas = 0.5 × Alas × Tinggi) sudah sesuai.');
    } else if (hasPartialFormula) {
      score += 12;
      feedback.push('⚠️ Teks Proses belum memuat rumus lengkap: "Luas = 0.5 * alas * tinggi".');
    } else if (processNodes.length > 0) {
      score += 5;
      feedback.push('⚠️ Teks Proses masih default. Ubah dengan rumus: "Luas = 0.5 * alas * tinggi".');
    } else {
      feedback.push('⚠️ Tambahkan simbol Proses (Persegi Panjang) dengan rumus: "Luas = 0.5 * alas * tinggi".');
    }

    // C. Teks Output Luas (15 Poin)
    let hasOutputLuas = false;
    ioNodes.forEach((node) => {
      const lbl = cleanLabel(node);
      if ((lbl.includes('cetak') || lbl.includes('output') || lbl.includes('tampilkan') || lbl.includes('print')) && lbl.includes('luas')) {
        hasOutputLuas = true;
      } else if (lbl === 'luas' || lbl.includes('luas segitiga')) {
        hasOutputLuas = true;
      }
    });

    if (hasOutputLuas) {
      score += 15;
      feedback.push('✅ Teks Output tepat: Terdeteksi pencetakan/keluaran nilai luas ke layar.');
    } else if (ioNodes.length > 1) {
      score += 8;
      feedback.push('⚠️ Teks Output belum spesifik. Tuliskan instruksi luaran seperti "Cetak Luas".');
    } else {
      feedback.push('⚠️ Tambahkan simbol Input/Output kedua untuk mencetak hasil luas: "Cetak Luas".');
    }

  } else if (caseKey === 'kelulusan') {
    // A. Teks Input Nilai (15 Poin)
    let hasInputNilai = false;
    ioNodes.forEach((node) => {
      const lbl = cleanLabel(node);
      if (lbl.includes('nilai') || lbl.includes('ujian') || lbl.includes('skor') || lbl.includes('score')) {
        hasInputNilai = true;
      }
    });

    if (hasInputNilai) {
      score += 15;
      feedback.push('✅ Teks Input tepat: Terdeteksi pembacaan masukan nilai ujian siswa.');
    } else if (ioNodes.length > 0) {
      score += 8;
      feedback.push('⚠️ Teks Input belum spesifik: Tuliskan masukan untuk nilai ujian (contoh: "Input Nilai Ujian").');
    } else {
      feedback.push('⚠️ Tambahkan simbol Input/Output (Jajar Genjang) untuk membaca nilai: "Input Nilai Ujian".');
    }

    // B. Teks Keputusan (15 Poin) + Cabang (10 Poin)
    let hasDecisionText = false;
    decisionNodes.forEach((node) => {
      const lbl = cleanLabel(node);
      if (lbl.includes('75') || lbl.includes('74') || lbl.includes('>=') || (lbl.includes('nilai') && (lbl.includes('>') || lbl.includes('<')))) {
        hasDecisionText = true;
      }
    });

    if (hasDecisionText) {
      score += 15;
      feedback.push('✅ Teks Keputusan tepat: Terdeteksi pengujian syarat kriteria kelulusan (Apakah Nilai >= 75?).');
    } else if (decisionNodes.length > 0) {
      score += 6;
      feedback.push('⚠️ Teks Keputusan belum spesifik. Tuliskan syarat kondisi: "Apakah Nilai >= 75?".');
    } else {
      feedback.push('⚠️ Tambahkan simbol Keputusan (Belah Ketupat) untuk memeriksa kondisi kelulusan: "Nilai >= 75?".');
    }

    // Check branching edges
    let hasTwoBranches = false;
    let hasBranchLabels = false;
    decisionNodes.forEach((node) => {
      const outEdges = edges.filter((e) => e.source === node.id);
      if (outEdges.length >= 2) {
        hasTwoBranches = true;
        const labels = outEdges.map((e) => (e.label || '').toLowerCase().trim());
        const handles = outEdges.map((e) => (e.sourceHandle || '').toLowerCase().trim());
        const hasYes = labels.includes('ya') || labels.includes('yes') || handles.includes('left');
        const hasNo = labels.includes('tidak') || labels.includes('no') || handles.includes('right');
        if (hasYes && hasNo) hasBranchLabels = true;
      }
    });

    if (hasTwoBranches && hasBranchLabels) {
      score += 10;
      feedback.push('✅ Struktur percabangan sangat baik: Simbol Keputusan memiliki 2 cabang alur keluar lengkap dengan cabang "Ya" dan "Tidak".');
    } else if (hasTwoBranches) {
      score += 5;
      feedback.push('⚠️ Simbol Keputusan memiliki cabang keluar, namun belum diberi label "Ya" dan "Tidak" yang jelas.');
    } else if (decisionNodes.length > 0) {
      feedback.push('⚠️ Simbol Keputusan harus menghubungkan minimal 2 cabang alur keluar (Cabang Ya dan Cabang Tidak).');
    }

    // C. Teks Output Lulus & Remedial (15 Poin)
    let hasLulus = false;
    let hasRemedial = false;
    const outputCandidates = [...ioNodes, ...processNodes];
    outputCandidates.forEach((node) => {
      const lbl = cleanLabel(node);
      if (lbl.includes('lulus') && !lbl.includes('tidak lulus')) hasLulus = true;
      if (lbl.includes('remedial') || lbl.includes('tidak lulus') || lbl.includes('gagal') || lbl.includes('mengulang')) hasRemedial = true;
    });

    if (hasLulus && hasRemedial) {
      score += 15;
      feedback.push('✅ Teks Output tepat: Terdeteksi penanganan kedua kemungkinan status kelulusan ("LULUS" dan "REMEDIAL").');
    } else if (hasLulus || hasRemedial) {
      score += 8;
      feedback.push('⚠️ Teks Output baru menangani satu status. Pastikan menampilkan kedua status: "LULUS" dan "REMEDIAL".');
    } else {
      feedback.push('⚠️ Tambahkan simbol Output untuk menampilkan hasil: "LULUS" dan "REMEDIAL".');
    }

  } else {
    // Dynamic or Bebas
    const expectedInputs = (currentCase.expected_inputs || '').toLowerCase().split(',').map((s) => s.trim()).filter(Boolean);
    const allIoText = ioNodes.map(cleanLabel).join(' ');
    let matchedInputs = expectedInputs.filter((kw) => allIoText.includes(kw));

    if (expectedInputs.length > 0) {
      if (matchedInputs.length > 0) {
        score += 15;
        feedback.push(`✅ Teks Input tepat: Terdeteksi masukan data (${matchedInputs.join(', ')}).`);
      } else {
        feedback.push(`⚠️ Masukkan data input yang sesuai: ${currentCase.expected_inputs || 'Data'}.`);
      }
    } else if (ioNodes.length > 0) {
      score += 15;
      feedback.push('✅ Teks Input terdeteksi dengan instruksi data.');
    }

    if (currentCase.flow_type === 'branching') {
      const decWords = (currentCase.expected_decision || '').toLowerCase().split(',').map((s) => s.trim()).filter(Boolean);
      let matchDec = false;
      decisionNodes.forEach((node) => {
        const lbl = cleanLabel(node);
        if (decWords.some((w) => lbl.includes(w)) || (lbl.length > 3 && lbl !== 'kondisi ?')) {
          matchDec = true;
        }
      });
      if (matchDec) {
        score += 15;
        feedback.push(`✅ Teks Keputusan tepat: Terdeteksi syarat kondisi (${currentCase.expected_decision || 'Kondisi'}).`);
      } else {
        feedback.push(`⚠️ Masukkan syarat keputusan: "${currentCase.expected_decision || 'Kondisi?'}".`);
      }

      // Check branches
      let hasTwoBranches = false;
      decisionNodes.forEach((node) => {
        const outEdges = edges.filter((e) => e.source === node.id);
        if (outEdges.length >= 2) hasTwoBranches = true;
      });
      if (hasTwoBranches) {
        score += 10;
        feedback.push('✅ Simbol Keputusan memiliki 2 cabang alur keluar.');
      }

      // Check yes/no outputs
      const expYes = (currentCase.expected_yes_output || '').toLowerCase().trim();
      const expNo = (currentCase.expected_no_output || '').toLowerCase().trim();
      let matchYes = false;
      let matchNo = false;
      [...ioNodes, ...processNodes].forEach((node) => {
        const lbl = cleanLabel(node);
        if (expYes && lbl.includes(expYes)) matchYes = true;
        if (expNo && lbl.includes(expNo)) matchNo = true;
      });
      if (expYes && expNo && matchYes && matchNo) {
        score += 15;
        feedback.push(`✅ Teks Output tepat: Terdeteksi penanganan hasil ('${expYes}' dan '${expNo}').`);
      } else if (ioNodes.length > 1) {
        score += 15;
        feedback.push('✅ Teks Output terdeteksi untuk kedua jalur.');
      } else {
        score += 8;
        feedback.push('💡 Tambahkan simbol Output untuk masing-masing cabang.');
      }
    } else {
      // Sequential process & output
      const procWords = (currentCase.expected_process || '').toLowerCase().split(',').map((s) => s.trim()).filter(Boolean);
      let matchProc = false;
      processNodes.forEach((node) => {
        const lbl = cleanLabel(node);
        if (procWords.some((w) => lbl.includes(w)) || (lbl.length > 3 && lbl !== 'hitung data')) {
          matchProc = true;
        }
      });
      if (matchProc) {
        score += 25;
        feedback.push(`✅ Teks Proses tepat: Rumus/proses (${currentCase.expected_process || 'Perhitungan'}) sesuai.`);
      } else if (processNodes.length > 0) {
        score += 10;
        feedback.push(`⚠️ Ubah teks proses dengan rumus yang sesuai.`);
      } else {
        feedback.push(`⚠️ Tambahkan simbol Proses (Persegi Panjang).`);
      }

      const outWords = (currentCase.expected_outputs || '').toLowerCase().split(',').map((s) => s.trim()).filter(Boolean);
      let matchOut = false;
      ioNodes.forEach((node) => {
        const lbl = cleanLabel(node);
        if (outWords.some((w) => lbl.includes(w)) || (lbl.includes('cetak') || lbl.includes('output'))) {
          matchOut = true;
        }
      });
      if (matchOut) {
        score += 15;
        feedback.push(`✅ Teks Output tepat: Menampilkan hasil luaran.`);
      } else if (ioNodes.length > 1) {
        score += 10;
        feedback.push('⚠️ Tuliskan instruksi luaran seperti "Cetak Hasil".');
      }
    }
  }

  // 3. EVALUASI PANAH (25 Poin)
  const nodeIds = nodes.map((n) => n.id);
  let validEdges = 0;
  edges.forEach((e) => {
    if (e.source && e.target && nodeIds.includes(e.source) && nodeIds.includes(e.target) && e.source !== e.target) {
      validEdges++;
    }
  });

  if (edges.length > 0 && validEdges === edges.length) {
    score += 25;
    feedback.push('✅ Alur konektor panah terhubung dengan runtut dan valid dari awal hingga akhir.');
  } else if (edges.length > 0) {
    score += 12;
    feedback.push('⚠️ Terdapat beberapa garis penghubung yang belum terpasang ke simbol yang sah.');
  } else {
    feedback.push('⚠️ Hubungkan simbol-simbol di atas menggunakan garis panah penghubung.');
  }

  return {
    score: Math.min(100, Math.max(0, score)),
    feedback,
  };
}

// ============================================================================
// KOMPONEN UTAMA EDITOR FLOWCHART SISWA
// ============================================================================
export default function FlowchartEditor() {
  const caseStudiesMap = React.useMemo(() => getCaseStudiesMap(), []);
  const caseKeys = Object.keys(caseStudiesMap);

  const [studentName, setStudentName] = useState(() => {
    const metaUser = document.querySelector('meta[name="user-name"]')?.getAttribute('content');
    if (metaUser && metaUser.trim()) return metaUser.trim();
    return localStorage.getItem('flowchart_student_name') || '';
  });

  const [studentClass, setStudentClass] = useState(() => {
    const metaClass = document.querySelector('meta[name="user-class"]')?.getAttribute('content');
    if (metaClass && metaClass.trim()) return metaClass.trim();
    return localStorage.getItem('flowchart_student_class') || '10 RPL 1';
  });

  const [selectedCaseKey, setSelectedCaseKey] = useState(() => {
    return caseKeys[0] || 'luas_segitiga';
  });

  // KANVAS DIMULAI DALAM KONDISI KOSONG AGAR SISWA MENYUSUN SENDIRI DARI AWAL
  const [nodes, setNodes, onNodesChange] = useNodesState([]);
  const [edges, setEdges, onEdgesChange] = useEdgesState([]);

  const [assessment, setAssessment] = useState(null);
  const [loading, setLoading] = useState(false);
  const [toast, setToast] = useState(null);
  const assessmentRef = useRef(null);

  // Otomatis scroll halus ke bagian umpan balik & skor saat evaluasi selesai
  useEffect(() => {
    if (assessment) {
      const timer = setTimeout(() => {
        const el = assessmentRef.current || document.getElementById('hasil-penilaian');
        if (el) {
          el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }, 100);
      return () => clearTimeout(timer);
    }
  }, [assessment]);

  const handleNameChange = (e) => {
    const name = e.target.value;
    setStudentName(name);
    localStorage.setItem('flowchart_student_name', name);
  };

  const handleClassChange = (e) => {
    const cls = e.target.value;
    setStudentClass(cls);
    localStorage.setItem('flowchart_student_class', cls);
  };

  const showToast = (message, type = 'info') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 3500);
  };

  const updateNodeLabel = useCallback((id, newLabel) => {
    setNodes((prev) =>
      prev.map((node) => {
        if (node.id === id) {
          return { ...node, data: { ...node.data, label: newLabel } };
        }
        return node;
      })
    );
  }, [setNodes]);

  const deleteNode = useCallback((id) => {
    setNodes((prev) => prev.filter((node) => node.id !== id));
    setEdges((prev) => prev.filter((edge) => edge.source !== id && edge.target !== id));
    showToast('Simbol berhasil dihapus.', 'info');
  }, [setNodes, setEdges]);

  const nodesWithHandlers = nodes.map((node) => ({
    ...node,
    data: {
      ...node.data,
      onChangeLabel: updateNodeLabel,
      onDeleteNode: deleteNode,
    },
  }));

  const onConnect = useCallback(
    (params) => {
      let edgeData = {
        ...params,
        animated: true,
        style: { stroke: '#2563eb', strokeWidth: 2 },
        markerEnd: { type: MarkerType.ArrowClosed, color: '#2563eb' },
      };

      // Otomatis atur label dan warna jika ditarik dari handle Ya (left) atau Tidak (right)
      if (params.sourceHandle === 'left') {
        edgeData = {
          ...edgeData,
          label: 'Ya',
          labelStyle: { fill: '#047857', fontWeight: 800, fontSize: 11 },
          labelBgPadding: [6, 4],
          labelBgBorderRadius: 4,
          labelBgStyle: { fill: '#ecfdf5', stroke: '#10b981', strokeWidth: 1.5 },
          style: { stroke: '#059669', strokeWidth: 2.5 },
          markerEnd: { type: MarkerType.ArrowClosed, color: '#059669' },
        };
      } else if (params.sourceHandle === 'right') {
        edgeData = {
          ...edgeData,
          label: 'Tidak',
          labelStyle: { fill: '#b91c1c', fontWeight: 800, fontSize: 11 },
          labelBgPadding: [6, 4],
          labelBgBorderRadius: 4,
          labelBgStyle: { fill: '#fef2f2', stroke: '#ef4444', strokeWidth: 1.5 },
          style: { stroke: '#dc2626', strokeWidth: 2.5 },
          markerEnd: { type: MarkerType.ArrowClosed, color: '#dc2626' },
        };
      }

      setEdges((prev) => addEdge(edgeData, prev));
      showToast(
        params.sourceHandle === 'left'
          ? 'Cabang [Ya] berhasil dihubungkan! ✅'
          : params.sourceHandle === 'right'
          ? 'Cabang [Tidak] berhasil dihubungkan! ❌'
          : 'Garis alir panah berhasil disambungkan! 🔗',
        'success'
      );
    },
    [setEdges]
  );

  // Klik garis panah untuk menghapus atau mengganti label (sangat mempermudah pengguna HP)
  const onEdgeClick = useCallback(
    (event, edge) => {
      const currentLbl = edge.label ? `"${edge.label}"` : '(tanpa label)';
      const choice = window.prompt(
        `Pengaturan Garis Panah [${currentLbl}]:\n\n` +
          `• Ketik "hapus" untuk MENGHAPUS garis ini\n` +
          `• Ketik "ya" untuk memberi label [Ya] (Hijau)\n` +
          `• Ketik "tidak" untuk memberi label [Tidak] (Merah)\n` +
          `• Atau ketik teks label lain yang diinginkan:`,
        edge.label || ''
      );

      if (choice === null) return;
      const val = choice.trim();

      if (val.toLowerCase() === 'hapus') {
        setEdges((prev) => prev.filter((e) => e.id !== edge.id));
        showToast('Garis panah berhasil dihapus.', 'info');
      } else if (val.toLowerCase() === 'ya') {
        setEdges((prev) =>
          prev.map((e) =>
            e.id === edge.id
              ? {
                  ...e,
                  label: 'Ya',
                  labelStyle: { fill: '#047857', fontWeight: 800, fontSize: 11 },
                  labelBgPadding: [6, 4],
                  labelBgBorderRadius: 4,
                  labelBgStyle: { fill: '#ecfdf5', stroke: '#10b981', strokeWidth: 1.5 },
                  style: { stroke: '#059669', strokeWidth: 2.5 },
                  markerEnd: { type: MarkerType.ArrowClosed, color: '#059669' },
                }
              : e
          )
        );
        showToast('Label panah diubah menjadi [Ya] ✅', 'success');
      } else if (val.toLowerCase() === 'tidak') {
        setEdges((prev) =>
          prev.map((e) =>
            e.id === edge.id
              ? {
                  ...e,
                  label: 'Tidak',
                  labelStyle: { fill: '#b91c1c', fontWeight: 800, fontSize: 11 },
                  labelBgPadding: [6, 4],
                  labelBgBorderRadius: 4,
                  labelBgStyle: { fill: '#fef2f2', stroke: '#ef4444', strokeWidth: 1.5 },
                  style: { stroke: '#dc2626', strokeWidth: 2.5 },
                  markerEnd: { type: MarkerType.ArrowClosed, color: '#dc2626' },
                }
              : e
          )
        );
        showToast('Label panah diubah menjadi [Tidak] ❌', 'success');
      } else if (val !== '') {
        setEdges((prev) =>
          prev.map((e) =>
            e.id === edge.id
              ? {
                  ...e,
                  label: val,
                  labelStyle: { fill: '#1e293b', fontWeight: 700, fontSize: 11 },
                }
              : e
          )
        );
        showToast(`Label panah diubah menjadi "${val}".`, 'info');
      }
    },
    [setEdges]
  );

  // Menambah simbol baru ke kanvas kosong
  const addSymbol = (type, defaultLabel) => {
    const newId = `node_${Date.now()}`;
    const xPos = 260 + (Math.random() * 80 - 40);
    const yPos = 120 + (nodes.length * 90);

    const newNode = {
      id: newId,
      type,
      position: { x: xPos, y: yPos },
      data: { label: defaultLabel },
    };

    setNodes((prev) => [...prev, newNode]);
    showToast(`Simbol ${type.toUpperCase()} ditambahkan ke kanvas.`, 'info');
  };

  // Pilih studi kasus (Kanvas tetap bersih / kosong)
  const handleSelectCase = (key) => {
    if (selectedCaseKey === key) return;
    const targetCase = caseStudiesMap[key] || {};

    if (nodes.length > 0) {
      if (confirm(`Pindah ke "${targetCase.title || key}"? Kanvas akan dikosongkan agar Anda dapat mengerjakan soal baru dari awal.`)) {
        setNodes([]);
        setEdges([]);
        setAssessment(null);
      } else {
        return;
      }
    }

    setSelectedCaseKey(key);
    showToast(`Studi kasus "${targetCase.title || key}" dipilih. Silakan rangkai flowchart Anda!`, 'info');
  };

  // Bersihkan kanvas
  const handleClearCanvas = () => {
    if (confirm('Yakin ingin mengosongkan kanvas?')) {
      setNodes([]);
      setEdges([]);
      setAssessment(null);
      showToast('Kanvas telah dikosongkan.', 'info');
    }
  };

  // Simpan & Validasi ke DB
  const handleValidateAndSave = async () => {
    if (nodes.length === 0) {
      alert('Kanvas masih kosong! Tambahkan simbol flowchart terlebih dahulu menggunakan tombol di atas.');
      return;
    }

    setLoading(true);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const payload = {
      student_name: studentName.trim() || 'Siswa Mandiri',
      class_name: studentClass.trim() || '10 RPL 1',
      case_key: selectedCaseKey,
      diagram_data: {
        case_key: selectedCaseKey,
        nodes: nodes.map(({ id, type, position, data }) => ({
          id,
          type,
          position,
          data: { label: data.label },
        })),
        edges,
      },
    };

    try {
      const res = await fetch('/flowchart/check', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken || '',
        },
        body: JSON.stringify(payload),
      });

      if (res.ok) {
        const data = await res.json();
        setAssessment({
          score: data.score,
          feedback: data.feedback,
          id: data.submission_id,
        });
        showToast(`🎉 Evaluasi selesai! Skor Anda: ${data.score}/100`, 'success');
      } else {
        throw new Error('API server unavailable');
      }
    } catch (err) {
      // Fallback otomatis ke evaluasi lokal (untuk GitHub Pages / mode offline)
      const localResult = evaluateDiagramLocally(nodes, edges, selectedCaseKey, currentCase);
      const subId = `sub_${Date.now()}`;
      setAssessment({
        score: localResult.score,
        feedback: localResult.feedback,
        id: subId,
      });

      try {
        const history = JSON.parse(localStorage.getItem('flowchart_submissions_history') || '[]');
        history.unshift({
          id: subId,
          student_name: studentName.trim() || 'Siswa Mandiri',
          class_name: studentClass.trim() || '10 RPL 1',
          case_key: selectedCaseKey,
          score: localResult.score,
          feedback: localResult.feedback,
          created_at: new Date().toISOString(),
        });
        localStorage.setItem('flowchart_submissions_history', JSON.stringify(history.slice(0, 30)));
      } catch (e) {}

      showToast(`🎉 Evaluasi selesai! Skor Anda: ${localResult.score}/100`, 'success');
    } finally {
      setLoading(false);
      setTimeout(() => {
        const el = assessmentRef.current || document.getElementById('hasil-penilaian');
        if (el) {
          el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }, 120);
    }
  };

  const currentCase = caseStudiesMap[selectedCaseKey] || caseStudiesMap[caseKeys[0]] || defaultCaseStudies.luas_segitiga;

  return (
    <div className="flex flex-col gap-5">

      {/* TOAST NOTIFIKASI */}
      {toast && (
        <div
          className={`fixed bottom-6 right-6 z-50 px-4 py-3 rounded-2xl shadow-xl border text-xs font-bold flex items-center gap-2 animate-bounce transition-all ${
            toast.type === 'success'
              ? 'bg-emerald-600 text-white border-emerald-500'
              : 'bg-slate-900 text-white border-slate-800'
          }`}
        >
          <span>{toast.message}</span>
        </div>
      )}

      {/* BAR ATAS: IDENTITAS SISWA & PILIHAN STUDI KASUS */}
      <div className="bg-white p-3.5 sm:p-5 rounded-2xl border border-slate-200/90 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-3 sm:gap-4">
        {/* Identitas Siswa & Kelas */}
        <div className="flex items-center gap-2.5 sm:gap-3 flex-1">
          <div className="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center font-bold text-base sm:text-lg flex-shrink-0">
            👤
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 flex-1">
            <div>
              <label className="block text-[10px] sm:text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-0.5">Nama Siswa</label>
              <input
                type="text"
                value={studentName}
                onChange={handleNameChange}
                placeholder="Ketik Nama Anda..."
                className="w-full px-2.5 sm:px-3 py-1.5 bg-slate-50/70 border border-slate-200 rounded-lg text-xs sm:text-sm text-slate-800 font-bold focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
              />
            </div>
            <div>
              <label className="block text-[10px] sm:text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-0.5">Kelas / Rombel</label>
              <input
                type="text"
                value={studentClass}
                onChange={handleClassChange}
                placeholder="10 RPL 1"
                className="w-full px-2.5 sm:px-3 py-1.5 bg-indigo-50/60 border border-indigo-200 rounded-lg text-xs sm:text-sm text-indigo-900 font-extrabold focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all"
              />
            </div>
          </div>
        </div>

        {/* Tab Pilihan Studi Kasus */}
        <div className="pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100">
          <span className="text-[11px] sm:text-xs font-bold text-slate-500 mb-1.5 flex items-center gap-1">
            <span>📚</span>
            <span>Pilih Kasus Soal ({caseKeys.length}):</span>
          </span>
          <div className="flex flex-wrap gap-1.5 sm:gap-2">
            {caseKeys.map((key) => {
              const item = caseStudiesMap[key] || {};
              const isSelected = selectedCaseKey === key;
              const shortTitle = (item.title || key)
                .replace(/^Studi Kasus \d+:\s*/i, '')
                .replace(/^Studi Kasus Mandiri:\s*/i, '');
              return (
                <button
                  key={key}
                  type="button"
                  onClick={() => handleSelectCase(key)}
                  className={`px-2.5 sm:px-3 py-1.5 rounded-xl text-xs font-bold border transition-all cursor-pointer flex items-center gap-1.5 ${
                    isSelected
                      ? 'bg-blue-600 border-blue-600 text-white shadow-sm ring-2 ring-blue-200'
                      : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100 hover:border-slate-300'
                  }`}
                >
                  <span>{item.icon || '📌'}</span>
                  <span className="truncate max-w-[130px] sm:max-w-none">{shortTitle}</span>
                </button>
              );
            })}
          </div>
        </div>
      </div>

      {/* KARTU PETUNJUK SOAL STUDI KASUS (PANDUAN SISWA) */}
      <div className="bg-gradient-to-r from-blue-50/70 via-indigo-50/40 to-sky-50/60 p-3.5 sm:p-5 rounded-2xl border border-blue-200 shadow-2xs">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 sm:gap-2 pb-2.5 sm:pb-3 border-b border-blue-200/60 mb-2 sm:mb-3">
          <div className="flex items-center gap-2">
            <span className="text-lg sm:text-xl">{currentCase.icon}</span>
            <div>
              <h3 className="text-xs sm:text-sm font-extrabold text-blue-950 flex flex-wrap items-center gap-1.5 sm:gap-2">
                <span>{currentCase.title}</span>
                <span className="text-[9px] sm:text-[10px] uppercase font-bold px-1.5 sm:px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 border border-blue-200">
                  {currentCase.badge}
                </span>
              </h3>
              <p className="text-[11px] sm:text-xs text-blue-700 font-semibold mt-0.5">{currentCase.formula}</p>
            </div>
          </div>
          <span className="text-[10px] sm:text-[11px] font-bold text-slate-500 bg-white px-2.5 py-0.5 sm:py-1 rounded-full border border-slate-200 self-start sm:self-auto">
            Kanvas Kosong
          </span>
        </div>

        <p className="text-[11px] sm:text-xs text-slate-700 leading-relaxed">
          <b>Instruksi Soal:</b> {currentCase.description}
        </p>
      </div>

      {/* PALET SIMBOL DENGAN WARNA SESUAI ASLINYA & TOMBOL AKSI */}
      <div className="bg-white p-3 sm:p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-col gap-3">
        {/* Palet Simbol */}
        <div>
          <span className="text-[11px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1">
            <span>🧩</span> Tambah Simbol:
          </span>
          <div className="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-1.5 sm:gap-2">
            {/* 1. Terminator */}
            <button
              type="button"
              onClick={() => addSymbol('terminator', 'Mulai')}
              className="flex items-center justify-center sm:justify-start gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-2 bg-amber-100 hover:bg-amber-200 text-amber-950 border border-amber-300 rounded-xl text-xs font-bold shadow-2xs transition-all active:scale-95 cursor-pointer"
              title="Terminator: Penanda Mulai atau Selesai"
            >
              <span className="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-amber-500 inline-block flex-shrink-0" />
              <span className="truncate">Terminator</span>
            </button>

            {/* 2. I/O */}
            <button
              type="button"
              onClick={() => addSymbol('io', 'Input Data')}
              className="flex items-center justify-center sm:justify-start gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-2 bg-sky-100 hover:bg-sky-200 text-sky-950 border border-sky-300 rounded-xl text-xs font-bold shadow-2xs transition-all active:scale-95 cursor-pointer"
              title="I/O: Masukan atau Luaran data"
            >
              <span className="w-2.5 h-2.5 sm:w-3 sm:h-3 bg-sky-500 -skew-x-12 inline-block rounded-xs flex-shrink-0" />
              <span className="truncate">Input / Output</span>
            </button>

            {/* 3. Proses */}
            <button
              type="button"
              onClick={() => addSymbol('process', 'Hitung Data')}
              className="flex items-center justify-center sm:justify-start gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-2 bg-emerald-100 hover:bg-emerald-200 text-emerald-950 border border-emerald-300 rounded-xl text-xs font-bold shadow-2xs transition-all active:scale-95 cursor-pointer"
              title="Proses: Perhitungan rumus atau operasi"
            >
              <span className="w-2.5 h-2.5 sm:w-3 sm:h-3 bg-emerald-500 inline-block rounded-xs flex-shrink-0" />
              <span className="truncate">Proses (Hitung)</span>
            </button>

            {/* 4. Keputusan */}
            <button
              type="button"
              onClick={() => addSymbol('decision', currentCase.expected_decision || 'Kondisi ?')}
              className="flex items-center justify-center sm:justify-start gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-2 bg-orange-100 hover:bg-orange-200 text-orange-950 border border-orange-300 rounded-xl text-xs font-bold shadow-2xs transition-all active:scale-95 cursor-pointer"
              title="Keputusan: Percabangan kondisi Ya/Tidak"
            >
              <span className="w-2.5 h-2.5 sm:w-3 sm:h-3 bg-orange-500 rotate-45 inline-block rounded-xs flex-shrink-0" />
              <span className="truncate">Keputusan</span>
            </button>
          </div>
        </div>

        {/* Tombol Aksi */}
        <div className="flex items-center gap-2 pt-2 border-t border-slate-100 sm:border-t-0 sm:pt-0 sm:ml-auto">
          <button
            type="button"
            onClick={handleClearCanvas}
            className="flex-1 sm:flex-none px-3 py-2 bg-slate-50 hover:bg-rose-50 text-rose-600 hover:text-rose-700 border border-rose-200 rounded-xl text-xs font-bold shadow-2xs transition-all cursor-pointer text-center"
            title="Kosongkan Kanvas"
          >
            🗑️ Kosongkan
          </button>

          <button
            type="button"
            onClick={handleValidateAndSave}
            disabled={loading}
            className="flex-2 sm:flex-none flex items-center justify-center gap-1.5 sm:gap-2 px-3.5 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition-all active:scale-95 disabled:opacity-50 cursor-pointer"
          >
            {loading ? (
              <>
                <span className="animate-spin">⏳</span>
                <span>Memeriksa...</span>
              </>
            ) : (
              <>
                <span>🚀</span>
                <span>Periksa & Simpan</span>
              </>
            )}
          </button>
        </div>
      </div>

      {/* KANVAS EDITOR DENGAN TAMPILAN BERSIH & MODERN */}
      <div className="relative w-full h-[460px] sm:h-[580px] bg-[#F8FAFC] border-2 border-slate-200 rounded-2xl sm:rounded-3xl overflow-hidden shadow-xs">
        <ReactFlow
          nodes={nodesWithHandlers}
          edges={edges}
          onNodesChange={onNodesChange}
          onEdgesChange={onEdgesChange}
          onConnect={onConnect}
          onEdgeClick={onEdgeClick}
          nodeTypes={nodeTypes}
          fitView
          defaultEdgeOptions={{
            animated: true,
            style: { stroke: '#2563eb', strokeWidth: 2 },
            markerEnd: { type: MarkerType.ArrowClosed, color: '#2563eb' },
          }}
        >
          <MiniMap
            zoomable
            pannable
            nodeColor={(n) => {
              if (n.type === 'terminator') return '#eab308';
              if (n.type === 'io') return '#0284c7';
              if (n.type === 'process') return '#16a34a';
              if (n.type === 'decision') return '#ea580c';
              return '#64748b';
            }}
            className="!hidden sm:!block !rounded-2xl !border !border-slate-300 !bg-white/90"
          />
          <Controls className="!bg-white !rounded-xl sm:!rounded-2xl !border !border-slate-200 !shadow-sm !left-2 !bottom-2 sm:!left-auto sm:!bottom-auto" />
          <Background color="#cbd5e1" gap={20} size={1.5} />
        </ReactFlow>

        {/* Petunjuk Kanvas Kosong (Muncul jika kanvas belum ada simbol) */}
        {nodes.length === 0 && (
          <div className="absolute inset-0 pointer-events-none flex flex-col items-center justify-center p-4 sm:p-6 text-center">
            <div className="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-white/90 border border-slate-200 flex items-center justify-center text-xl sm:text-2xl shadow-xs mb-2 sm:mb-3">
              ✏️
            </div>
            <h4 className="text-xs sm:text-sm font-extrabold text-slate-700">Kanvas Masih Kosong</h4>
            <p className="text-[11px] sm:text-xs text-slate-500 max-w-sm mt-0.5 sm:mt-1 leading-relaxed">
              Klik tombol simbol di atas untuk menambah kotak alur, lalu tarik lingkaran kecil untuk menyambungkan panah.
            </p>
          </div>
        )}

        <div className="absolute top-2 left-2 right-2 sm:right-auto bg-white/95 backdrop-blur-xs px-2.5 py-1.5 rounded-lg sm:rounded-xl border border-slate-200 shadow-2xs text-[10px] sm:text-[11px] text-slate-600 pointer-events-none flex items-center gap-1.5">
          <span>💡 <b>Tips:</b> Tarik titik <b>Hijau (Ya)</b> & <b>Merah (Tidak)</b> pada simbol Keputusan. Ketuk garis panah untuk menghapus atau mengganti label.</span>
        </div>
      </div>

      {/* PANEL HASIL PENILAIAN & EVALUASI OTOMATIS */}
      {assessment && (
        <div 
          ref={assessmentRef}
          id="hasil-penilaian"
          className="bg-white border-2 border-blue-300 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-md animate-fadeIn scroll-mt-20 sm:scroll-mt-24 ring-4 ring-blue-500/10"
        >
          <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-3 sm:pb-4 border-b border-slate-100 gap-3">
            <div className="flex items-center gap-3">
              <div
                className={`w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl flex items-center justify-center text-lg sm:text-xl font-black shadow-md flex-shrink-0 ${
                  assessment.score >= 80
                    ? 'bg-emerald-500 text-white'
                    : assessment.score >= 60
                    ? 'bg-amber-500 text-white'
                    : 'bg-rose-500 text-white'
                }`}
              >
                {assessment.score}
              </div>
              <div>
                <h3 className="text-sm sm:text-base font-extrabold text-slate-900 leading-snug">
                  {assessment.score === 100
                    ? '🌟 Luar Biasa! Diagram Sangat Sempurna'
                    : assessment.score >= 80
                    ? '👍 Bagus Sekali! Struktur Alur Sudah Tepat'
                    : assessment.score >= 60
                    ? '✏️ Cukup Baik, Ada Beberapa Catatan Perbaikan'
                    : '💪 Masih Perlu Dilengkapi, Perhatikan Catatan di Bawah'}
                </h3>
                <p className="text-[11px] sm:text-xs text-slate-500 mt-0.5">
                  Submission #{assessment.id} • {studentName} ({studentClass})
                </p>
              </div>
            </div>

            <div className="self-start sm:self-auto sm:text-right">
              <span className="inline-block text-[11px] sm:text-xs font-black px-3 py-1 sm:px-3.5 sm:py-1.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-full">
                Skor Akhir: {assessment.score} / 100
              </span>
            </div>
          </div>

          <div className="mt-3.5 sm:mt-4">
            <h4 className="text-[11px] sm:text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
              Umpan Balik Evaluasi Logika Flowchart:
            </h4>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-2 sm:gap-2.5">
              {assessment.feedback.map((item, idx) => {
                const isPositive = item.includes('✅') || item.includes('tersedia') || item.includes('valid') || item.includes('disertakan');
                return (
                  <div
                    key={idx}
                    className={`p-2.5 sm:p-3 rounded-xl border text-[11px] sm:text-xs font-semibold flex items-start gap-2 ${
                      isPositive
                        ? 'bg-emerald-50/70 border-emerald-200 text-emerald-900'
                        : 'bg-amber-50/70 border-amber-200 text-amber-900'
                    }`}
                  >
                    <span className="text-sm flex-shrink-0">{isPositive ? '✅' : '⚠️'}</span>
                    <span className="mt-0.5 leading-snug">{item.replace(/^[✅⚠️]\s*/, '')}</span>
                  </div>
                );
              })}
            </div>
          </div>
        </div>
      )}

    </div>
  );
}

// Inisialisasi Root React
function initFlowchartApp() {
  const container = document.getElementById('flowchart-app');
  if (container && !container.dataset.reactMounted) {
    container.dataset.reactMounted = 'true';
    const root = createRoot(container);
    root.render(<FlowchartEditor />);
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initFlowchartApp);
} else {
  initFlowchartApp();
}
