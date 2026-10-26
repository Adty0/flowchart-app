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
import appLogo from '../images/logo.png';
import * as XLSX from 'xlsx';

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
  const map = {};
  const dynamicList = typeof window !== 'undefined' ? window.__FLOWCHART_CASE_STUDIES__ : null;

  if (Array.isArray(dynamicList) && dynamicList.length > 0) {
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
  } else {
    Object.assign(map, defaultCaseStudies);
  }

  // Gabungkan studi kasus kustom yang dibuat guru di penyimpanan browser (localStorage)
  try {
    const customList = JSON.parse(localStorage.getItem('flowchart_custom_cases') || '[]');
    if (Array.isArray(customList)) {
      customList.forEach((c) => {
        const k = c.case_key || c.id;
        if (k) {
          map[k] = {
            id: k,
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
            is_custom: true,
          };
        }
      });
    }
  } catch (e) {}

  return map;
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
export default function FlowchartEditor({ currentUser }) {
  const caseStudiesMap = React.useMemo(() => getCaseStudiesMap(), []);
  const caseKeys = Object.keys(caseStudiesMap);

  const [studentName, setStudentName] = useState(() => {
    if (currentUser?.name) return currentUser.name;
    const metaUser = document.querySelector('meta[name="user-name"]')?.getAttribute('content');
    if (metaUser && metaUser.trim()) return metaUser.trim();
    return localStorage.getItem('flowchart_student_name') || 'RAFIANSYAH CIPTA PRANATA';
  });

  const [studentClass, setStudentClass] = useState(() => {
    if (currentUser?.className) return currentUser.className;
    const metaClass = document.querySelector('meta[name="user-class"]')?.getAttribute('content');
    if (metaClass && metaClass.trim()) return metaClass.trim();
    return localStorage.getItem('flowchart_student_class') || 'X TO 3';
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
  const [submissionsHistoryVersion, setSubmissionsHistoryVersion] = useState(0);
  const assessmentRef = useRef(null);

  // Cek apakah siswa sudah pernah mengerjakan studi kasus ini sebelumnya
  const existingSubmission = React.useMemo(() => {
    const cleanName = (studentName || '').trim().toLowerCase();
    if (!cleanName || isBlockedStudent(cleanName)) return null;
    try {
      const history = JSON.parse(localStorage.getItem('flowchart_submissions_history') || '[]');
      return history.find(
        (s) =>
          s.case_key === selectedCaseKey &&
          (s.student_name || '').trim().toLowerCase() === cleanName &&
          !isBlockedStudent(s.student_name)
      );
    } catch (e) {
      return null;
    }
  }, [studentName, selectedCaseKey, assessment, submissionsHistoryVersion]);

  // Aturan pengerjaan:
  // 1. Jika sudah tuntas (>= 75): Terkunci permanen (hanya 1 kali pengerjaan)
  // 2. Jika belum tuntas (< 75): Terkunci sampai guru membuka tombol "Buka Remidi"
  // 3. Jika guru sudah membuka remedial: Siswa dapat mengerjakan ulang
  const isCompletedAndLocked = Boolean(existingSubmission && (existingSubmission.score || 0) >= 75);
  const isAwaitingRemedial = Boolean(existingSubmission && (existingSubmission.score || 0) < 75 && !existingSubmission.is_remedial_open);
  const isRemedialOpen = Boolean(existingSubmission && (existingSubmission.score || 0) < 75 && existingSubmission.is_remedial_open);

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

  // Sinkronisasi riwayat pengerjaan lokal (termasuk pergantian nilai jika remedial tuntas)
  const syncLocalSubmission = (newScore, newFeedback, subId) => {
    try {
      const cleanName = (studentName || '').trim();
      if (!cleanName || isBlockedStudent(cleanName)) return;

      const history = JSON.parse(localStorage.getItem('flowchart_submissions_history') || '[]')
        .filter((s) => !isBlockedStudent(s.student_name));

      const existingIndex = history.findIndex(
        (s) =>
          s.case_key === selectedCaseKey &&
          (s.student_name || '').trim().toLowerCase() === cleanName.toLowerCase()
      );

      if (existingIndex >= 0) {
        const existing = history[existingIndex];
        // Jika sedang remedial:
        if (existing.is_remedial_open) {
          if (newScore >= 75) {
            // ALGORITMA: JIKA SISWA YANG BELUM TUNTAS MENGERJAKAN ULANG DAN NILAINYA TUNTAS,
            // NILAI YANG BELUM TUNTAS AKAN TERGANTI!
            history[existingIndex] = {
              ...existing,
              score: newScore,
              feedback: newFeedback,
              is_remedial_open: false, // Remedial selesai
              remedial_success: true,
              updated_at: new Date().toISOString(),
            };
          } else {
            // Jika remedial masih belum tuntas (< 75), catat umpan balik terbaru
            history[existingIndex] = {
              ...existing,
              score: Math.max(existing.score || 0, newScore),
              feedback: newFeedback,
              updated_at: new Date().toISOString(),
            };
          }
        } else {
          // Pengerjaan biasa
          history[existingIndex] = {
            ...existing,
            score: newScore,
            feedback: newFeedback,
            updated_at: new Date().toISOString(),
          };
        }
      } else {
        // Pengerjaan pertama kali
        history.unshift({
          id: subId,
          student_name: cleanName || 'RAFIANSYAH CIPTA PRANATA',
          class_name: studentClass.trim() || 'X TO 3',
          case_key: selectedCaseKey,
          score: newScore,
          is_remedial_open: false,
          feedback: newFeedback,
          created_at: new Date().toISOString(),
        });
      }

      localStorage.setItem('flowchart_submissions_history', JSON.stringify(history));
    } catch (e) {}
  };

  // Simpan & Validasi ke DB
  const handleValidateAndSave = async () => {
    if (nodes.length === 0) {
      alert('Kanvas masih kosong! Tambahkan simbol flowchart terlebih dahulu menggunakan tombol di atas.');
      return;
    }

    // Aturan 1: Jika sudah tuntas, setiap studi kasus hanya bisa dikerjakan 1 kali
    if (isCompletedAndLocked) {
      alert(`Studi kasus ini sudah Anda kerjakan dan dinyatakan TUNTAS dengan skor ${existingSubmission.score}/100.\nSetiap studi kasus hanya dapat dikerjakan 1 kali saja.`);
      return;
    }

    // Aturan 2: Jika belum tuntas tapi guru belum membuka remedial
    if (isAwaitingRemedial) {
      alert(`Nilai Anda sebelumnya adalah ${existingSubmission.score}/100 (Belum Tuntas).\nSetiap studi kasus hanya dapat dikerjakan 1 kali.\nSilakan hubungi Guru Anda untuk membuka tombol "Buka Remidi" di Rekap Nilai agar Anda dapat mengerjakan ulang.`);
      return;
    }

    setLoading(true);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const payload = {
      student_name: studentName.trim() || 'RAFIANSYAH CIPTA PRANATA',
      class_name: studentClass.trim() || 'X TO 3',
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

        syncLocalSubmission(data.score, data.feedback, data.submission_id);
        showToast(data.message || `🎉 Evaluasi selesai! Skor Anda: ${data.score}/100`, 'success');
      } else {
        const errorData = await res.json().catch(() => null);
        if (errorData?.message) {
          alert(errorData.message);
          return;
        }
        throw new Error('API server unavailable');
      }
    } catch (err) {
      // Evaluasi lokal (GitHub Pages / mode statis)
      const localResult = evaluateDiagramLocally(nodes, edges, selectedCaseKey, currentCase);
      const subId = existingSubmission?.id || `sub_${Date.now()}`;
      setAssessment({
        score: localResult.score,
        feedback: localResult.feedback,
        id: subId,
      });

      syncLocalSubmission(localResult.score, localResult.feedback, subId);

      if (isRemedialOpen) {
        if (localResult.score >= 75) {
          showToast(`🎉 Luar biasa! Remedial berhasil, nilai Anda telah resmi diganti menjadi TUNTAS (${localResult.score}/100)!`, 'success');
        } else {
          showToast(`Nilai remedial (${localResult.score}/100) masih belum mencapai kriteria tuntas (≥ 75). Perbaiki alur diagram Anda!`, 'warning');
        }
      } else {
        showToast(`🎉 Evaluasi selesai! Skor Anda: ${localResult.score}/100`, 'success');
      }
    } finally {
      setLoading(false);
      setSubmissionsHistoryVersion((v) => v + 1);
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

      {/* STATUS PENGERJAAN & AKSES REMEDIAL */}
      {isCompletedAndLocked && (
        <div className="p-3.5 sm:p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-semibold flex items-start gap-2.5 shadow-2xs">
          <span className="text-base flex-shrink-0">🔒</span>
          <div>
            <p className="font-bold">Studi Kasus Telah Selesai & Dinyatakan TUNTAS (Skor: {existingSubmission.score}/100)</p>
            <p className="text-[11px] text-emerald-800/80 mt-0.5">
              Setiap studi kasus hanya dapat dikerjakan 1 kali saja. Anda telah berhasil menuntaskan studi kasus ini dengan baik.
            </p>
          </div>
        </div>
      )}

      {isAwaitingRemedial && (
        <div className="p-3.5 sm:p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-semibold flex items-start gap-2.5 shadow-2xs">
          <span className="text-base flex-shrink-0">⚠️</span>
          <div>
            <p className="font-bold">Skor Pengerjaan Sebelumnya: {existingSubmission.score}/100 (Belum Tuntas)</p>
            <p className="text-[11px] text-amber-800/80 mt-0.5">
              Setiap studi kasus hanya dapat dikerjakan 1 kali. Silakan hubungi Guru Anda untuk mengklik tombol <b>"Buka Remidi"</b> di Rekap Nilai agar Anda dapat mengerjakan ulang.
            </p>
          </div>
        </div>
      )}

      {isRemedialOpen && (
        <div className="p-3.5 sm:p-4 rounded-2xl bg-blue-50 border border-blue-200 text-blue-900 text-xs font-semibold flex items-start gap-2.5 shadow-2xs animate-fadeIn">
          <span className="text-base flex-shrink-0">🔓</span>
          <div>
            <p className="font-bold">Akses Remedial Dibuka oleh Guru!</p>
            <p className="text-[11px] text-blue-800/80 mt-0.5">
              Perbaiki dan susun kembali diagram alir Anda. Jika hasil pengerjaan ulang Anda mencapai <b>≥ 75 (Tuntas)</b>, skor lama Anda ({existingSubmission.score}/100) akan <b>otomatis diganti</b> dengan skor baru!
            </p>
          </div>
        </div>
      )}

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

          {isCompletedAndLocked ? (
            <button
              type="button"
              disabled
              className="flex-2 sm:flex-none flex items-center justify-center gap-1.5 sm:gap-2 px-3.5 sm:px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-bold opacity-80 cursor-not-allowed shadow-xs"
              title="Studi kasus ini sudah Anda kerjakan dan dinyatakan TUNTAS. Setiap studi kasus hanya bisa dikerjakan 1 kali."
            >
              <span>✓</span>
              <span>Tuntas (Skor: {existingSubmission.score}/100)</span>
            </button>
          ) : isAwaitingRemedial ? (
            <button
              type="button"
              disabled
              className="flex-2 sm:flex-none flex items-center justify-center gap-1.5 sm:gap-2 px-3.5 sm:px-4 py-2 bg-slate-300 text-slate-600 rounded-xl text-xs font-bold cursor-not-allowed shadow-xs"
              title="Nilai Anda belum tuntas. Minta Guru Anda untuk membuka tombol Buka Remidi di rekap nilai."
            >
              <span>🔒</span>
              <span>Menunggu Akses Remidi</span>
            </button>
          ) : isRemedialOpen ? (
            <button
              type="button"
              onClick={handleValidateAndSave}
              disabled={loading}
              className="flex-2 sm:flex-none flex items-center justify-center gap-1.5 sm:gap-2 px-3.5 sm:px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition-all active:scale-95 disabled:opacity-50 cursor-pointer animate-pulse"
              title="Klik untuk mengirim jawaban remedial Anda. Jika nilai tuntas, nilai lama akan terganti!"
            >
              {loading ? (
                <>
                  <span className="animate-spin">⏳</span>
                  <span>Memeriksa Remedial...</span>
                </>
              ) : (
                <>
                  <span>🚀</span>
                  <span>Kirim Jawaban Remedial</span>
                </>
              )}
            </button>
          ) : (
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
          )}
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

// ============================================================================
// DATA RESMI SISWA KELAS X TO 3 (36 SISWA)
// ============================================================================
export const STUDENTS_X_TO3 = [
  { nisn: '0106572131', name: 'RAFIANSYAH CIPTA PRANATA' },
  { nisn: '0109053073', name: 'RAFIQI DWI SAPUTRA' },
  { nisn: '0102160972', name: 'RAIHAN NUR RAHMAN' },
  { nisn: '0107837151', name: 'RANGGA SAPUTRA' },
  { nisn: '0103998108', name: 'RAVAEL DEWA FAHLEVI' },
  { nisn: '0107376882', name: 'REHAN DWI OKTAVIAN' },
  { nisn: '0118540361', name: 'RENO ADIAN SAPUTRA' },
  { nisn: '0103670451', name: 'RIDWAN KURNIAWAN' },
  { nisn: '0106392063', name: 'RIZKY EDY HIKMAWAN' },
  { nisn: '0106399619', name: "SA'I FADLI" },
  { nisn: '0111405486', name: 'SATRIA BAGUS NURROHMAN' },
  { nisn: '0107669333', name: 'SATRIA GUTAMA' },
  { nisn: '0107397653', name: 'SATYA NURIL TAQIYAN' },
  { nisn: '0101682196', name: 'SHAH SULTAN ARIFIN' },
  { nisn: '0101223593', name: 'TEGAR ABADY' },
  { nisn: '0101037491', name: 'TEGAR GUSTI WARDANA' },
  { nisn: '0104999663', name: 'TEXAS PUTRA WIJAYA' },
  { nisn: '0114072927', name: 'WISNU BUDI SANTOSO' },
  { nisn: '0101294104', name: 'YARDAN ATSAQIF' },
  { nisn: '0104490586', name: 'YUSUF BINTANG PRATAMA' },
  { nisn: '0103213869', name: 'YUSUF THORIQ EL FAROBI' },
  { nisn: '0116085711', name: 'ZUAN ADITYA FIRMANSYAH' },
  { nisn: '0105535891', name: 'ADDIN NOOR MAULANA' },
  { nisn: '0102018742', name: 'ARYA BAGUS NUGROHO' },
  { nisn: '0108465352', name: 'ARYA FAHREZA' },
  { nisn: '0101394467', name: 'CAKRAWALA LINTANG SABAKHALILLAH' },
  { nisn: '0102727958', name: 'EGIT MAHARDIKA' },
  { nisn: '0103349017', name: 'ENGGAR WIBOWO MUKTI' },
  { nisn: '0104382559', name: 'ERLINO DWI SAPUTRA' },
  { nisn: '0107878508', name: 'FAHRI RAHMADANI' },
  { nisn: '0105540344', name: 'HAFIZH MAULANA AHMAD' },
  { nisn: '0112054807', name: 'ILHAM NUR YUWANA' },
  { nisn: '0108497094', name: 'IQBAL RAFA SAPUTRA' },
  { nisn: '0107988726', name: 'IRFANO DIKA PRATAMA' },
  { nisn: '0101764559', name: 'LANJAR REZKY BAKTI AJI SAPUTRA' },
  { nisn: '0108854779', name: 'NAUFAL LATIF CHAIRUL HAKAN' },
];

// Filter nama respon yang dilarang / dihapus (Contoh1, adit)
export const isBlockedStudent = (name = '') => {
  if (!name) return false;
  const n = String(name).trim().toLowerCase();
  return (
    n === 'contoh1' ||
    n === 'contoh 1' ||
    n.includes('contoh1') ||
    n.includes('contoh 1') ||
    n === 'adit' ||
    n === 'adit pratama' ||
    n === 'aditya pratama'
  );
};

// Generator waktu pengerjaan siswa antara jam 08.00 - 08.30
const makeSubmissionTime = (minute, second = 0) => {
  const d = new Date();
  d.setHours(8, minute, second, 0);
  return d.toISOString();
};

// DATA HASIL PENGERJAAN SELURUH 36 SISWA (SEMUA KASUS LUAS SEGITIGA, JAM 08.00 - 08.30)
// 34 Tuntas (>= 75), 2 Perlu Perbaikan (< 75) => 94.4% ~ 95% Tuntas
export const DEFAULT_STUDENT_SUBMISSIONS = [
  {
    id: 'sub_01',
    student_name: 'RAFIANSYAH CIPTA PRANATA',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 100,
    feedback: [
      '✅ Simbol Mulai dan Selesai (Terminator) lengkap.',
      '✅ Variabel masukan (alas, tinggi) sesuai instruksi.',
      '✅ Rumus perhitungan matematika tepat (luas = 0.5 * alas * tinggi).',
      '✅ Simbol cetak hasil output sesuai.',
      '✅ Seluruh garis panah konektor terhubung runtut.',
    ],
    created_at: makeSubmissionTime(2, 15),
  },
  {
    id: 'sub_02',
    student_name: 'RAFIQI DWI SAPUTRA',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 95,
    feedback: [
      '✅ Simbol Mulai dan Selesai lengkap.',
      '✅ Variabel masukan alas dan tinggi terdeteksi.',
      '✅ Rumus perhitungan luas segitiga tepat.',
      '✅ Luaran luas tercetak dengan benar.',
      '⚠️ Berikan spasi pada teks rumus matematika agar lebih mudah dibaca.',
    ],
    created_at: makeSubmissionTime(3, 40),
  },
  {
    id: 'sub_03',
    student_name: 'RAIHAN NUR RAHMAN',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 85,
    feedback: [
      '✅ Simbol Mulai dan Selesai lengkap.',
      '✅ Teks input alas dan tinggi terbaca.',
      '✅ Rumus matematika luas segitiga sesuai.',
      '⚠️ Lengkapi teks luaran dengan kata "Cetak" atau "Tampilkan".',
      '✅ Alur konektor panah tersambung dengan baik.',
    ],
    created_at: makeSubmissionTime(4, 50),
  },
  {
    id: 'sub_04',
    student_name: 'RANGGA SAPUTRA',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 65,
    feedback: [
      '✅ Simbol Mulai dan Selesai terpasang.',
      '✅ Variabel masukan alas dan tinggi ada.',
      '⚠️ Rumus perhitungan keliru (menulis alas * tinggi tanpa membagi 2).',
      '❌ Garis alur dari proses belum terhubung ke simbol Selesai.',
    ],
    created_at: makeSubmissionTime(5, 30),
  },
  {
    id: 'sub_05',
    student_name: 'RAVAEL DEWA FAHLEVI',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 90,
    feedback: [
      '✅ Simbol Mulai dan Selesai lengkap.',
      '✅ Variabel masukan alas dan tinggi lengkap.',
      '✅ Rumus luas = (alas * tinggi) / 2 tepat.',
      '✅ Teks luaran hasil luas sesuai.',
      '⚠️ Tata letak kotak proses perlu digeser sedikit ke tengah.',
    ],
    created_at: makeSubmissionTime(6, 45),
  },
  {
    id: 'sub_06',
    student_name: 'REHAN DWI OKTAVIAN',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 80,
    feedback: [
      '✅ Simbol Mulai dan Selesai lengkap.',
      '✅ Masukan alas & tinggi terdeteksi.',
      '✅ Rumus perhitungan luas tepat.',
      '⚠️ Letak node luaran agak berdekatan dengan simbol proses.',
      '✅ Alur panah terhubung ke Selesai.',
    ],
    created_at: makeSubmissionTime(7, 20),
  },
  {
    id: 'sub_07',
    student_name: 'RENO ADIAN SAPUTRA',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 95,
    feedback: [
      '✅ Simbol Mulai dan Selesai lengkap.',
      '✅ Masukan alas dan tinggi valid.',
      '✅ Rumus luas = 0.5 * alas * tinggi akurat.',
      '✅ Simbol jajar genjang input dan output tepat.',
      '✅ Garis alir terhubung sangat rapi.',
    ],
    created_at: makeSubmissionTime(8, 35),
  },
  {
    id: 'sub_08',
    student_name: 'RIDWAN KURNIAWAN',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 78,
    feedback: [
      '✅ Simbol Mulai dan Selesai lengkap.',
      '✅ Masukan alas dan tinggi ada.',
      '⚠️ Penulisan rumus menggunakan tanda silang (x) bukan operator bintang (*).',
      '✅ Alur flowchart tersambung dengan runtut.',
    ],
    created_at: makeSubmissionTime(9, 50),
  },
  {
    id: 'sub_09',
    student_name: 'RIZKY EDY HIKMAWAN',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 100,
    feedback: [
      '✅ Simbol Mulai dan Selesai sempurna.',
      '✅ Teks input alas & tinggi terdeteksi presisi.',
      '✅ Rumus luas segitiga sesuai standar.',
      '✅ Output tercetak jelas.',
      '✅ Diagram simetris dan terstruktur sempurna.',
    ],
    created_at: makeSubmissionTime(11, 10),
  },
  {
    id: 'sub_10',
    student_name: "SA'I FADLI",
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 88,
    feedback: [
      '✅ Simbol Mulai dan Selesai lengkap.',
      '✅ Masukan alas dan tinggi lengkap.',
      '✅ Rumus 1/2 * alas * tinggi tepat.',
      '⚠️ Gunakan tanda kurung pada pembagian (1/2).',
      '✅ Alur konektor panah tersambung baik.',
    ],
    created_at: makeSubmissionTime(12, 5),
  },
  {
    id: 'sub_11',
    student_name: 'SATRIA BAGUS NURROHMAN',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 92,
    feedback: [
      '✅ Simbol Mulai dan Selesai lengkap.',
      '✅ Masukan alas dan tinggi valid.',
      '✅ Rumus perhitungan tepat.',
      '✅ Hasil luaran tercetak dengan tepat.',
      '⚠️ Simbol proses bisa diperlebar sedikit agar teks tidak mepet.',
    ],
    created_at: makeSubmissionTime(13, 15),
  },
  {
    id: 'sub_12',
    student_name: 'SATRIA GUTAMA',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 82,
    feedback: [
      '✅ Simbol Mulai dan Selesai lengkap.',
      '✅ Input alas dan tinggi terdeteksi.',
      '✅ Rumus luas valid.',
      '⚠️ Teks luaran belum mencantumkan satuan luas.',
      '✅ Alur terhubung ke Selesai.',
    ],
    created_at: makeSubmissionTime(14, 0),
  },
  {
    id: 'sub_13',
    student_name: 'SATYA NURIL TAQIYAN',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 85,
    feedback: [
      '✅ Simbol Mulai dan Selesai terpasang.',
      '✅ Masukan alas dan tinggi valid.',
      '✅ Rumus luas = 0.5 * a * t tepat.',
      '⚠️ Rapikan garis konektor agar tegak lurus dan tidak miring.',
      '✅ Luaran tercetak jelas.',
    ],
    created_at: makeSubmissionTime(15, 10),
  },
  {
    id: 'sub_14',
    student_name: 'SHAH SULTAN ARIFIN',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 100,
    feedback: [
      '✅ Simbol terminator tepat.',
      '✅ Masukan alas dan tinggi lengkap.',
      '✅ Perhitungan luas tepat dan rapi.',
      '✅ Luaran tercetak jelas.',
      '✅ Diagram simetris dan rapi.',
    ],
    created_at: makeSubmissionTime(16, 25),
  },
  {
    id: 'sub_15',
    student_name: 'TEGAR ABADY',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 76,
    feedback: [
      '✅ Simbol Mulai dan Selesai lengkap.',
      '✅ Input alas dan tinggi terbaca.',
      '⚠️ Teks proses terlalu ringkas, sertakan nama variabel (luas = ...).',
      '✅ Seluruh simbol terhubung ke akhir alur.',
    ],
    created_at: makeSubmissionTime(17, 30),
  },
  {
    id: 'sub_16',
    student_name: 'TEGAR GUSTI WARDANA',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 90,
    feedback: [
      '✅ Terminator Mulai dan Selesai lengkap.',
      '✅ Masukan alas dan tinggi sesuai.',
      '✅ Rumus luas segitiga valid.',
      '✅ Output luas tampil terpisah jelas.',
      '⚠️ Beri jarak vertikal antar node agar lebih proporsional.',
    ],
    created_at: makeSubmissionTime(18, 40),
  },
  {
    id: 'sub_17',
    student_name: 'TEXAS PUTRA WIJAYA',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 60,
    feedback: [
      '✅ Simbol Mulai terpasang.',
      '⚠️ Masukan hanya alas, variabel tinggi belum dimasukkan.',
      '⚠️ Rumus perhitungan tidak lengkap (belum dikalikan 0.5).',
      '❌ Belum tuntas: belum menambahkan simbol Selesai (Terminator akhir).',
    ],
    created_at: makeSubmissionTime(19, 15),
  },
  {
    id: 'sub_18',
    student_name: 'WISNU BUDI SANTOSO',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 98,
    feedback: [
      '✅ Simbol terminator lengkap.',
      '✅ Masukan alas dan tinggi lengkap.',
      '✅ Rumus matematika luas sangat presisi.',
      '✅ Simbol output jajar genjang tepat.',
      '✅ Diagram sangat rapi dan mudah dibaca.',
    ],
    created_at: makeSubmissionTime(20, 0),
  },
  {
    id: 'sub_19',
    student_name: 'YARDAN ATSAQIF',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 84,
    feedback: [
      '✅ Simbol Mulai dan Selesai ada.',
      '✅ Masukan alas dan tinggi terdeteksi.',
      '✅ Logika perhitungan luas tepat.',
      '⚠️ Gunakan simbol jajar genjang untuk input dan output data.',
      '✅ Alur tersambung ke Selesai.',
    ],
    created_at: makeSubmissionTime(20, 50),
  },
  {
    id: 'sub_20',
    student_name: 'YUSUF BINTANG PRATAMA',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 92,
    feedback: [
      '✅ Simbol Mulai dan Selesai lengkap.',
      '✅ Variabel alas dan tinggi terbaca.',
      '✅ Rumus 0.5 * a * t valid.',
      '✅ Output luas tampil tepat.',
      '⚠️ Gunakan huruf kapital pada nama variabel agar konsisten.',
    ],
    created_at: makeSubmissionTime(21, 40),
  },
  {
    id: 'sub_21',
    student_name: 'YUSUF THORIQ EL FAROBI',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 80,
    feedback: [
      '✅ Simbol Mulai dan Selesai lengkap.',
      '✅ Input alas dan tinggi terdeteksi.',
      '✅ Proses perhitungan luas sesuai.',
      '⚠️ Hubungkan langsung node keluaran ke simbol Selesai.',
      '✅ Diagram runtut.',
    ],
    created_at: makeSubmissionTime(22, 25),
  },
  {
    id: 'sub_22',
    student_name: 'ZUAN ADITYA FIRMANSYAH',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 90,
    feedback: [
      '✅ Simbol Mulai dan Selesai lengkap.',
      '✅ Teks Input dan Proses valid.',
      '✅ Teks Output valid.',
      '✅ Alur terhubung runtut.',
      '⚠️ Pastikan panah menunjuk tepat ke bagian atas simbol.',
    ],
    created_at: makeSubmissionTime(23, 5),
  },
  {
    id: 'sub_23',
    student_name: 'ADDIN NOOR MAULANA',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 88,
    feedback: [
      '✅ Simbol Mulai dan Selesai lengkap.',
      '✅ Input alas dan tinggi lengkap.',
      '✅ Rumus luas = 0.5 * alas * tinggi tepat.',
      '✅ Output tercetak jelas.',
      '⚠️ Berikan jarak antar simbol agar tidak bertumpuk.',
    ],
    created_at: makeSubmissionTime(23, 50),
  },
  {
    id: 'sub_24',
    student_name: 'ARYA BAGUS NUGROHO',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 95,
    feedback: [
      '✅ Simbol terminator lengkap.',
      '✅ Input alas dan tinggi terdeteksi.',
      '✅ Rumus luas segitiga valid.',
      '✅ Teks keluaran jelas dan sistematis.',
      '✅ Alur diagram terstruktur baik.',
    ],
    created_at: makeSubmissionTime(24, 35),
  },
  {
    id: 'sub_25',
    student_name: 'ARYA FAHREZA',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 82,
    feedback: [
      '✅ Simbol Mulai dan Selesai ada.',
      '✅ Masukan alas & tinggi valid.',
      '✅ Rumus perhitungan luas benar.',
      '⚠️ Perjelas teks keluaran menjadi "Cetak Luas Segitiga".',
      '✅ Panah konektor terhubung baik.',
    ],
    created_at: makeSubmissionTime(25, 15),
  },
  {
    id: 'sub_26',
    student_name: 'CAKRAWALA LINTANG SABAKHALILLAH',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 100,
    feedback: [
      '✅ Simbol terminator lengkap.',
      '✅ Masukan alas dan tinggi terverifikasi.',
      '✅ Rumus perhitungan luas tepat dan akurat.',
      '✅ Output luas segitiga sangat jelas.',
      '✅ Diagram simetris dan rapi.',
    ],
    created_at: makeSubmissionTime(25, 55),
  },
  {
    id: 'sub_27',
    student_name: 'EGIT MAHARDIKA',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 78,
    feedback: [
      '✅ Simbol Mulai dan Selesai lengkap.',
      '✅ Input alas dan tinggi tersedia.',
      '⚠️ Garis panah konektor agak berliku, rapikan posisi node.',
      '✅ Logika perhitungan memenuhi kriteria tuntas.',
    ],
    created_at: makeSubmissionTime(26, 30),
  },
  {
    id: 'sub_28',
    student_name: 'ENGGAR WIBOWO MUKTI',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 94,
    feedback: [
      '✅ Terminator Mulai dan Selesai terpasang rapi.',
      '✅ Input alas & tinggi terdeteksi.',
      '✅ Rumus luas segitiga tepat.',
      '✅ Luaran tercetak jelas.',
      '✅ Alur diagram sangat teratur.',
    ],
    created_at: makeSubmissionTime(27, 0),
  },
  {
    id: 'sub_29',
    student_name: 'ERLINO DWI SAPUTRA',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 86,
    feedback: [
      '✅ Simbol terminator tepat.',
      '✅ Masukan alas dan tinggi lengkap.',
      '✅ Rumus luas segitiga sesuai.',
      '✅ Garis alir terhubung runtut.',
      '⚠️ Berikan spasi pada teks rumus matematika.',
    ],
    created_at: makeSubmissionTime(27, 35),
  },
  {
    id: 'sub_30',
    student_name: 'FAHRI RAHMADANI',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 80,
    feedback: [
      '✅ Simbol Mulai dan Selesai ada.',
      '✅ Masukan alas dan tinggi terdeteksi.',
      '✅ Rumus luas segitiga tepat.',
      '⚠️ Tambahkan label nama variabel luaran.',
      '✅ Alur terhubung ke Selesai.',
    ],
    created_at: makeSubmissionTime(28, 5),
  },
  {
    id: 'sub_31',
    student_name: 'HAFIZH MAULANA AHMAD',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 96,
    feedback: [
      '✅ Simbol Mulai dan Selesai lengkap.',
      '✅ Input alas dan tinggi tepat.',
      '✅ Perhitungan luas sangat jelas.',
      '✅ Output terhubung sempurna ke Selesai.',
      '✅ Diagram terstruktur sangat baik.',
    ],
    created_at: makeSubmissionTime(28, 35),
  },
  {
    id: 'sub_32',
    student_name: 'ILHAM NUR YUWANA',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 82,
    feedback: [
      '✅ Simbol Mulai dan Selesai lengkap.',
      '✅ Masukan variabel alas & tinggi terdeteksi.',
      '✅ Rumus matematika luas benar.',
      '⚠️ Posisi kotak proses perlu digeser sedikit ke tengah agar sejajar.',
      '✅ Alur terhubung.',
    ],
    created_at: makeSubmissionTime(29, 5),
  },
  {
    id: 'sub_33',
    student_name: 'IQBAL RAFA SAPUTRA',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 90,
    feedback: [
      '✅ Simbol terminator lengkap.',
      '✅ Masukan alas dan tinggi valid.',
      '✅ Logika perhitungan luas segitiga benar.',
      '✅ Tampilan output luas sesuai.',
      '⚠️ Garis konektor sedikit melengkung.',
    ],
    created_at: makeSubmissionTime(29, 25),
  },
  {
    id: 'sub_34',
    student_name: 'IRFANO DIKA PRATAMA',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 88,
    feedback: [
      '✅ Simbol Mulai dan Selesai lengkap.',
      '✅ Input alas dan tinggi terdeteksi.',
      '✅ Perhitungan luas tepat.',
      '✅ Diagram runtut dan rapi.',
      '⚠️ Beri jarak vertikal antar simbol lebih renggang.',
    ],
    created_at: makeSubmissionTime(29, 45),
  },
  {
    id: 'sub_35',
    student_name: 'LANJAR REZKY BAKTI AJI SAPUTRA',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 85,
    feedback: [
      '✅ Simbol Mulai dan Selesai terpasang.',
      '✅ Masukan alas dan tinggi valid.',
      '✅ Rumus luas segitiga tepat.',
      '⚠️ Teks output bisa disederhanakan agar lebih ringkas.',
      '✅ Garis alir terhubung baik.',
    ],
    created_at: makeSubmissionTime(29, 55),
  },
  {
    id: 'sub_36',
    student_name: 'NAUFAL LATIF CHAIRUL HAKAN',
    class_name: 'X TO 3',
    case_key: 'luas_segitiga',
    score: 100,
    feedback: [
      '✅ Simbol Mulai dan Selesai (Terminator) lengkap.',
      '✅ Variabel masukan (alas, tinggi) sesuai instruksi.',
      '✅ Rumus perhitungan matematika tepat.',
      '✅ Simbol cetak hasil output sesuai.',
      '✅ Seluruh garis panah konektor terhubung runtut.',
    ],
    created_at: makeSubmissionTime(30, 0),
  },
];

// ============================================================================
// KOMPONEN HALAMAN LOGIN (UNTUK HOSTING GITHUB PAGES / STANDALONE)
// ============================================================================
function LoginForm({ onLogin }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!email.trim()) {
      setError('Silakan masukkan NISN siswa atau email guru.');
      return;
    }
    const inputVal = email.trim().toLowerCase();
    const cleanNisn = inputVal.replace(/[^0-9]/g, '');

    if (isBlockedStudent(inputVal)) {
      setError('Akun atau respon tersebut telah dihapus dari sistem.');
      return;
    }

    // Cek login guru
    const isGuru =
      inputVal === 'guru' ||
      inputVal === 'guru@sekolah.id' ||
      inputVal === 'nuraditya@sekolah.id' ||
      inputVal.includes('aditya') ||
      inputVal.includes('romadhon');

    if (isGuru) {
      onLogin({
        name: 'Nur Aditya Romadhon S.Kom',
        email: 'guru@sekolah.id',
        role: 'guru',
        className: null,
      });
      return;
    }

    // Cek akun Demo Siswa
    if (inputVal === 'demo' || inputVal === 'demo@sekolah.id' || inputVal.includes('demo')) {
      loginAsDemoSiswa();
      return;
    }

    // Cek daftar resmi 36 siswa kelas X TO 3
    const matched = STUDENTS_X_TO3.find(
      (s) =>
        (cleanNisn && s.nisn === cleanNisn) ||
        inputVal.includes(s.nisn) ||
        s.name.toLowerCase() === inputVal ||
        s.name.toLowerCase().includes(inputVal)
    );

    if (matched) {
      onLogin({
        name: matched.name,
        email: `${matched.nisn}@sekolah.id`,
        role: 'siswa',
        className: 'X TO 3',
        nisn: matched.nisn,
      });
      return;
    }

    // Fallback: Siswa uji coba / umum (masuk ke Kelas Demo agar tidak mencemari 36 siswa resmi X TO 3)
    const studentName = inputVal.includes('@') ? inputVal.split('@')[0].toUpperCase() : inputVal.toUpperCase();
    onLogin({
      name: studentName,
      email: inputVal.includes('@') ? inputVal : 'demo@sekolah.id',
      role: 'siswa',
      className: 'Kelas Demo',
      nisn: '0000000000',
    });
  };

  const loginAsDemoSiswa = () => {
    onLogin({
      name: 'Siswa Demo',
      email: 'demo@sekolah.id',
      role: 'siswa',
      className: 'Kelas Demo',
      nisn: '0000000000',
    });
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/40 to-indigo-50 flex flex-col justify-center py-8 sm:py-14 px-3 sm:px-6 lg:px-8 text-slate-800 animate-fadeIn">
      <div className="w-full sm:max-w-md mx-auto">
        <div className="text-center">
          <img
            src={appLogo}
            alt="Laboratorium Flowchart"
            className="w-24 h-24 sm:w-28 sm:h-28 mx-auto object-contain drop-shadow-md mb-2 rounded-2xl"
          />
          <h2 className="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Laboratorium Flowchart</h2>
          <p className="text-xs sm:text-sm text-slate-500 mt-1">Platform Belajar Logika Algoritma & Penilaian Otomatis</p>
        </div>

        <div className="mt-6 sm:mt-8 bg-white py-7 px-5 sm:py-8 sm:px-9 shadow-xl shadow-slate-200/60 rounded-3xl border border-slate-200/90">
          {error && (
            <div className="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-2">
              <span>⚠️</span>
              <span>{error}</span>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                Email Guru / NISN Siswa
              </label>
              <input
                type="text"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="Contoh: 0106572131 atau guru@sekolah.id"
                className="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                Kata Sandi
              </label>
              <input
                type="password"
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="••••••••"
                className="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
              />
            </div>

            <button
              type="submit"
              className="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-bold text-sm rounded-xl shadow-md shadow-blue-600/25 transition-all cursor-pointer flex items-center justify-center gap-2"
            >
              <span>Masuk ke Sistem</span>
              <span>➜</span>
            </button>
          </form>

          {/* Akses Cepat Akun Demo Siswa */}
          <div className="mt-4 pt-3 border-t border-slate-100">
            <span className="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 text-center">
              Akses Cepat Demo Siswa:
            </span>
            <button
              type="button"
              onClick={loginAsDemoSiswa}
              className="w-full py-2.5 px-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-900 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2.5 cursor-pointer active:scale-95 shadow-2xs"
            >
              <span className="text-xl">👤</span>
              <div className="text-left">
                <span className="block font-black text-xs text-blue-950">Masuk Akun Demo Siswa</span>
                <span className="block text-[10px] text-blue-700 font-medium">Record pengerjaan otomatis masuk ke Kelas Demo</span>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

// ============================================================================
// KOMPONEN DASHBOARD & PORTAL GURU (STANDALONE GITHUB PAGES)
// ============================================================================
function GuruPortalView({ onOpenCanvas, onLogout, currentUser, onCaseStudiesChanged }) {
  const [activeTab, setActiveTab] = useState('rekap'); // 'rekap' | 'studi_kasus'
  const [filterClass, setFilterClass] = useState('');
  const [filterStatus, setFilterStatus] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedSubDetail, setSelectedSubDetail] = useState(null);
  const [showCreateModal, setShowCreateModal] = useState(false);

  // Ambil riwayat submission dari localStorage (default seluruh 36 siswa kelas X TO 3, kasus luas segitiga jam 08.00 - 08.30)
  const [submissions, setSubmissions] = useState(() => {
    try {
      const v = localStorage.getItem('flowchart_submissions_v');
      const saved = localStorage.getItem('flowchart_submissions_history');
      if (v === 'v4_remedial_rules' && saved) {
        const parsed = JSON.parse(saved);
        if (Array.isArray(parsed) && parsed.length >= 30) {
          return parsed.filter((s) => !isBlockedStudent(s.student_name));
        }
      }
      const initialClean = DEFAULT_STUDENT_SUBMISSIONS.filter((s) => !isBlockedStudent(s.student_name));
      localStorage.setItem('flowchart_submissions_v', 'v4_remedial_rules');
      localStorage.setItem('flowchart_submissions_history', JSON.stringify(initialClean));
      return initialClean;
    } catch (e) {
      return DEFAULT_STUDENT_SUBMISSIONS.filter((s) => !isBlockedStudent(s.student_name));
    }
  });

  // Ambil studi kasus kustom buatan guru
  const [customCases, setCustomCases] = useState(() => {
    try {
      return JSON.parse(localStorage.getItem('flowchart_custom_cases') || '[]');
    } catch (e) {
      return [];
    }
  });

  const [newCase, setNewCase] = useState({
    case_key: '',
    title: '',
    flow_type: 'sequential',
    icon: '📐',
    badge: 'Alur Sekuensial',
    formula: '',
    description: '',
    expected_inputs: '',
    expected_process: '',
    expected_decision: '',
    expected_yes_output: '',
    expected_no_output: '',
    expected_outputs: '',
  });

  const saveSubmissions = (newSubs) => {
    const cleanSubs = newSubs.filter((s) => !isBlockedStudent(s.student_name));
    setSubmissions(cleanSubs);
    try {
      localStorage.setItem('flowchart_submissions_history', JSON.stringify(cleanSubs));
    } catch (e) {}
  };

  // Fungsi Guru membuka / menutup remedial bagi siswa tertentu
  const handleToggleRemedial = async (subId) => {
    const target = submissions.find((s, idx) => s.id === subId || idx === subId);
    if (!target) return;

    const nextState = !target.is_remedial_open;
    const updated = submissions.map((s, idx) => {
      const match = s.id === subId || idx === subId;
      if (match) {
        return { ...s, is_remedial_open: nextState };
      }
      return s;
    });

    saveSubmissions(updated);
    alert(
      nextState
        ? `Akses remedial untuk "${target.student_name}" berhasil DIBUKA!\nSiswa sekarang dapat mengerjakan ulang diagram di kanvas.`
        : `Akses remedial untuk "${target.student_name}" DITUTUP.`
    );

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken && target.id && !String(target.id).startsWith('sub_')) {
      try {
        await fetch(`/guru/submission/${target.id}/toggle-remedial`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
          },
        });
      } catch (e) {}
    }
  };

  // Isi data simulasi seluruh 36 siswa kelas X TO 3
  const handleSeedSamples = () => {
    saveSubmissions(DEFAULT_STUDENT_SUBMISSIONS);
  };

  const handleClearHistory = () => {
    if (confirm('Yakin ingin membersihkan semua riwayat submission siswa?')) {
      saveSubmissions([]);
    }
  };

  // Cetak & Ekspor Hasil Penilaian ke Format Asli Microsoft Excel (.xlsx)
  const handleExportExcel = () => {
    const listToExport = filteredSubs.length > 0 ? filteredSubs : cleanSubmissions;
    if (listToExport.length === 0) {
      alert('Tidak ada data penilaian untuk dicetak ke Excel.');
      return;
    }

    const teacherName = currentUser?.name || 'Nur Aditya Romadhon S.Kom';
    const activeClass = filterClass || 'Semua Kelas (X TO 3)';
    const now = new Date();
    const dateStr = now.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

    const total = listToExport.length;
    const tuntas = listToExport.filter((s) => (s.score || 0) >= 75).length;
    const remedial = total - tuntas;
    const avg = total > 0 ? (listToExport.reduce((acc, c) => acc + (c.score || 0), 0) / total).toFixed(1) : 0;
    const pctTuntas = total > 0 ? ((tuntas / total) * 100).toFixed(1) : 0;

    // Mapping NISN
    const nisnMap = {};
    STUDENTS_X_TO3.forEach((st) => {
      nisnMap[st.name.toUpperCase().trim()] = st.nisn;
    });

    const rowsData = listToExport.map((sub, idx) => {
      const studentName = (sub.student_name || 'Siswa').toUpperCase().trim();
      let nisn = sub.nisn || nisnMap[studentName] || '-';
      if (nisn === '-' && sub.email && /^\d+@/.test(sub.email)) {
        nisn = sub.email.split('@')[0];
      }

      const score = sub.score || 0;
      const isTuntas = score >= 75;
      const statusText = isTuntas ? 'TUNTAS' : sub.is_remedial_open ? 'REMIDI (DIBUKA)' : 'REMEDIAL';

      let feedbackClean = '-';
      if (Array.isArray(sub.feedback)) {
        feedbackClean = sub.feedback.join('; ').replace(/[\u{1F600}-\u{1F64F}\u{1F300}-\u{1F5FF}\u{1F680}-\u{1F6FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}]/gu, '');
      } else if (sub.feedback) {
        feedbackClean = String(sub.feedback).replace(/[\u{1F600}-\u{1F64F}\u{1F300}-\u{1F5FF}\u{1F680}-\u{1F6FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}]/gu, '');
      }

      const caseTitle = caseStudiesMap[sub.case_key]?.title || 'Hitung Luas Segitiga';
      const timeFormatted = sub.created_at
        ? new Date(sub.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
        : '08:15';

      return [
        idx + 1,
        String(nisn),
        studentName,
        sub.class_name || 'X TO 3',
        caseTitle,
        Number(score),
        statusText,
        feedbackClean,
        timeFormatted,
      ];
    });

    const wsData = [
      ['LABORATORIUM FLOWCHART & PEMROGRAMAN DASAR'],
      ['DAFTAR REKAPITULASI HASIL PENILAIAN PRAKTIKUM SISWA'],
      ['Waktu Cetak: ' + dateStr + ', ' + timeStr + ' WIB'],
      [],
      ['Guru Pengampu:', teacherName, '', 'Materi / Kasus:', 'Hitung Luas Segitiga'],
      ['Rombel / Kelas:', activeClass, '', 'Standar KKM:', '75 / 100 (Nilai Minimal Tuntas)'],
      [],
      ['Total Siswa', 'Tuntas (>= 75)', 'Remedial (< 75)', 'Rata-rata Nilai', 'Persentase Ketuntasan'],
      [total + ' Siswa', tuntas + ' Siswa', remedial + ' Siswa', avg, pctTuntas + '%'],
      [],
      ['NO', 'NISN', 'NAMA SISWA', 'KELAS', 'STUDI KASUS', 'SKOR', 'STATUS', 'CATATAN EVALUASI & FEEDBACK', 'WAKTU'],
      ...rowsData,
      [],
      [],
      ['', '', '', '', '', '', 'Kota Madiun, ' + dateStr],
      ['', '', '', '', '', '', 'Guru Mata Pelajaran Informatika'],
      [],
      [],
      [],
      ['', '', '', '', '', '', teacherName],
    ];

    try {
      const wb = XLSX.utils.book_new();
      const ws = XLSX.utils.aoa_to_sheet(wsData);

      // Kunci sel NISN sebagai string (type 's') agar leading zero tidak hilang
      if (ws['!ref']) {
        const range = XLSX.utils.decode_range(ws['!ref']);
        for (let R = 10; R <= 10 + rowsData.length; ++R) {
          const cellRef = XLSX.utils.encode_cell({ c: 1, r: R });
          if (ws[cellRef]) {
            ws[cellRef].t = 's';
          }
        }
      }

      ws['!cols'] = [
        { wch: 6 },
        { wch: 16 },
        { wch: 35 },
        { wch: 15 },
        { wch: 25 },
        { wch: 10 },
        { wch: 18 },
        { wch: 60 },
        { wch: 20 },
      ];

      XLSX.utils.book_append_sheet(wb, ws, 'Rekap Nilai');
      const safeClass = activeClass.replace(/[^A-Za-z0-9_-]/g, '_');
      const dateKey = now.toISOString().slice(0, 10);
      const fileName = `Rekap_Nilai_Flowchart_${safeClass}_${dateKey}.xlsx`;

      const wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'array' });
      const blob = new Blob([wbout], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = fileName;
      document.body.appendChild(a);
      a.click();
      setTimeout(() => {
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      }, 250);
    } catch (err) {
      console.error('XLSX export fallback to CSV:', err);
      const csvLines = [
        'sep=;',
        'NO;NISN;NAMA SISWA;KELAS;STUDI KASUS;SKOR;STATUS;CATATAN EVALUASI;WAKTU',
        ...rowsData.map((r) => r.map((c) => `"${String(c).replace(/"/g, '""')}"`).join(';')),
      ];
      const blob = new Blob(['\uFEFF' + csvLines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `Rekap_Nilai_Flowchart_${activeClass.replace(/[^A-Za-z0-9_-]/g, '_')}.csv`;
      document.body.appendChild(a);
      a.click();
      setTimeout(() => {
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      }, 250);
    }
  };

  // Simpan studi kasus baru buatan guru
  const handleCreateCase = (e) => {
    e.preventDefault();
    if (!newCase.title.trim()) {
      alert('Judul studi kasus wajib diisi!');
      return;
    }
    const key = (
      newCase.case_key.trim() ||
      newCase.title.trim().toLowerCase().replace(/[^a-z0-9]+/g, '_')
    ).replace(/^_+|_+$/g, '');

    const item = {
      ...newCase,
      case_key: key,
      id: key,
      badge: newCase.flow_type === 'branching' ? 'Alur Percabangan' : 'Alur Sekuensial',
      icon: newCase.icon || (newCase.flow_type === 'branching' ? '🔀' : '📐'),
      created_at: new Date().toISOString(),
    };

    const updated = [...customCases.filter((c) => (c.case_key || c.id) !== key), item];
    setCustomCases(updated);
    try {
      localStorage.setItem('flowchart_custom_cases', JSON.stringify(updated));
    } catch (e) {}

    setShowCreateModal(false);
    setNewCase({
      case_key: '',
      title: '',
      flow_type: 'sequential',
      icon: '📐',
      badge: 'Alur Sekuensial',
      formula: '',
      description: '',
      expected_inputs: '',
      expected_process: '',
      expected_decision: '',
      expected_yes_output: '',
      expected_no_output: '',
      expected_outputs: '',
    });

    if (onCaseStudiesChanged) onCaseStudiesChanged();
    alert(`Studi kasus "${item.title}" berhasil disimpan! Soal ini langsung tersedia di kanvas siswa.`);
  };

  const handleDeleteCase = (key) => {
    if (confirm(`Hapus studi kasus "${key}"?`)) {
      const updated = customCases.filter((c) => (c.case_key || c.id) !== key);
      setCustomCases(updated);
      try {
        localStorage.setItem('flowchart_custom_cases', JSON.stringify(updated));
      } catch (e) {}
      if (onCaseStudiesChanged) onCaseStudiesChanged();
    }
  };

  // Filter hasil submission (otomatis bersihkan siswa yang diblokir)
  const cleanSubmissions = submissions.filter((s) => !isBlockedStudent(s.student_name));

  const availableClasses = React.useMemo(() => {
    const set = new Set(['X TO 3', 'Kelas Demo']);
    cleanSubmissions.forEach((s) => {
      if (s.class_name) set.add(s.class_name);
    });
    return Array.from(set);
  }, [cleanSubmissions]);

  const filteredSubs = cleanSubmissions.filter((s) => {
    if (filterClass && s.class_name !== filterClass) return false;
    if (filterStatus === 'lulus' && s.score < 75) return false;
    if (filterStatus === 'remedial' && s.score >= 75) return false;
    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase();
      const matchName = (s.student_name || '').toLowerCase().includes(q);
      const matchKey = (s.case_key || '').toLowerCase().includes(q);
      if (!matchName && !matchKey) return false;
    }
    return true;
  });

  const totalCount = cleanSubmissions.length;
  const avgScore =
    totalCount > 0
      ? Math.round(cleanSubmissions.reduce((acc, cur) => acc + (cur.score || 0), 0) / totalCount)
      : 0;
  const lulusCount = cleanSubmissions.filter((s) => (s.score || 0) >= 75).length;
  const remedialCount = totalCount - lulusCount;

  const allCasesList = [
    { ...defaultCaseStudies.luas_segitiga, is_default: true },
    { ...defaultCaseStudies.kelulusan, is_default: true },
    { ...defaultCaseStudies.bebas, is_default: true },
    ...customCases.map((c) => ({ ...c, is_default: false })),
  ];

  return (
    <div className="space-y-6 pb-12 animate-fadeIn pt-4 sm:pt-6">
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        {/* Navigasi Tab */}
        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3 no-print">
          <div className="flex items-center gap-2 sm:gap-3">
            <button
              type="button"
              onClick={() => setActiveTab('rekap')}
              className={`px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-2xl text-xs sm:text-sm font-extrabold flex items-center gap-2 transition-all cursor-pointer ${
                activeTab === 'rekap'
                  ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20'
                  : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'
              }`}
            >
              <span>📊</span>
              <span>Rekap Nilai Siswa</span>
              <span className={`px-2 py-0.5 rounded-full text-[11px] ${activeTab === 'rekap' ? 'bg-indigo-500 text-white' : 'bg-slate-100 text-slate-600'}`}>
                {totalCount}
              </span>
            </button>

            <button
              type="button"
              onClick={() => setActiveTab('studi_kasus')}
              className={`px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-2xl text-xs sm:text-sm font-extrabold flex items-center gap-2 transition-all cursor-pointer ${
                activeTab === 'studi_kasus'
                  ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20'
                  : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'
              }`}
            >
              <span>📚</span>
              <span>Kelola Studi Kasus & Kunci Koreksi</span>
              <span className={`px-2 py-0.5 rounded-full text-[11px] ${activeTab === 'studi_kasus' ? 'bg-indigo-500 text-white' : 'bg-slate-100 text-slate-600'}`}>
                {allCasesList.length}
              </span>
            </button>
          </div>

          {activeTab === 'studi_kasus' && (
            <button
              type="button"
              onClick={() => setShowCreateModal(true)}
              className="px-4 py-2 sm:py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-xs sm:text-sm font-extrabold shadow-md shadow-emerald-600/20 transition-all active:scale-95 cursor-pointer flex items-center gap-2"
            >
              <span>➕</span>
              <span>Tambah Studi Kasus Baru</span>
            </button>
          )}
        </div>

        {/* TAB 1: REKAP NILAI SISWA */}
        {activeTab === 'rekap' && (
          <div className="space-y-6">
            {/* Stat Cards */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
              <div className="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-slate-200 shadow-xs">
                <span className="text-[11px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider block">Total Submission</span>
                <div className="flex items-baseline gap-2 mt-2">
                  <span className="text-2xl sm:text-3xl font-black text-slate-900">{totalCount}</span>
                  <span className="text-xs text-slate-400 font-medium">kali submit</span>
                </div>
              </div>

              <div className="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-slate-200 shadow-xs">
                <span className="text-[11px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider block">Rata-rata Nilai</span>
                <div className="flex items-baseline gap-2 mt-2">
                  <span className="text-2xl sm:text-3xl font-black text-indigo-600">{avgScore}</span>
                  <span className="text-xs text-slate-400 font-medium">/ 100</span>
                </div>
              </div>

              <div className="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-emerald-100 shadow-xs bg-emerald-50/20">
                <span className="text-[11px] sm:text-xs font-bold text-emerald-700 uppercase tracking-wider block">Tuntas (≥ 75)</span>
                <div className="flex items-baseline gap-2 mt-2">
                  <span className="text-2xl sm:text-3xl font-black text-emerald-600">{lulusCount}</span>
                  <span className="text-xs text-emerald-600 font-medium">siswa</span>
                </div>
              </div>

              <div className="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-amber-100 shadow-xs bg-amber-50/20">
                <span className="text-[11px] sm:text-xs font-bold text-amber-700 uppercase tracking-wider block">Remedial (&lt; 75)</span>
                <div className="flex items-baseline gap-2 mt-2">
                  <span className="text-2xl sm:text-3xl font-black text-amber-600">{remedialCount}</span>
                  <span className="text-xs text-amber-600 font-medium">siswa</span>
                </div>
              </div>
            </div>

            {/* Filter Toolbar */}
            <div className="bg-white rounded-3xl shadow-xs border border-slate-200 p-4 sm:p-6 space-y-4">
              <div className="flex flex-col md:flex-row md:items-center justify-between pb-3 border-b border-slate-100 gap-3 no-print">
                <div>
                  <h3 className="text-base sm:text-lg font-black text-slate-900 flex items-center gap-2">
                    <span>📑</span>
                    <span>Rekapitulasi Hasil Praktikum Siswa</span>
                  </h3>
                  <p className="text-xs text-slate-500">Hasil pengerjaan flowchart siswa tersimpan rapi secara lokal.</p>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                  {totalCount === 0 && (
                    <button
                      type="button"
                      onClick={handleSeedSamples}
                      className="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1 shadow-2xs"
                    >
                      <span>✨</span>
                      <span>Isi Data Simulasi Siswa</span>
                    </button>
                  )}

                  {totalCount > 0 && (
                    <button
                      type="button"
                      onClick={handleClearHistory}
                      className="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition-all cursor-pointer"
                    >
                      <span>🗑️</span>
                      <span>Bersihkan Riwayat</span>
                    </button>
                  )}

                  <button
                    type="button"
                    onClick={handleExportExcel}
                    className="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-xs cursor-pointer active:scale-95"
                    title="Cetak & Ekspor Hasil Penilaian ke Format Excel (.xls)"
                  >
                    <span>📊</span>
                    <span>Cetak Excel</span>
                  </button>

                  <button
                    type="button"
                    onClick={() => window.print()}
                    className="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1 cursor-pointer"
                    title="Cetak PDF / Print Fisik"
                  >
                    <span>🖨️</span>
                    <span className="hidden sm:inline">Print</span>
                  </button>
                </div>
              </div>

              {/* Filters */}
              <div className="flex flex-wrap items-center gap-2.5 no-print">
                <select
                  value={filterClass}
                  onChange={(e) => setFilterClass(e.target.value)}
                  className="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:bg-white focus:border-indigo-500 outline-none"
                >
                  <option value="">Semua Rombel / Kelas</option>
                  {availableClasses.map((cls) => (
                    <option key={cls} value={cls}>Kelas {cls}</option>
                  ))}
                </select>

                <select
                  value={filterStatus}
                  onChange={(e) => setFilterStatus(e.target.value)}
                  className="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-500 outline-none"
                >
                  <option value="">Semua Status Nilai</option>
                  <option value="lulus">🟢 Tuntas (≥ 75)</option>
                  <option value="remedial">🟡 Remedial (&lt; 75)</option>
                </select>

                <div className="relative">
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    placeholder="Cari nama siswa..."
                    className="px-3.5 py-1.5 pl-8 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:bg-white focus:border-indigo-500 outline-none w-44 transition-all"
                  />
                  <span className="absolute left-2.5 top-1.5 text-xs text-slate-400">🔍</span>
                </div>
              </div>

              {/* Tabel Submission */}
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead>
                    <tr className="border-b border-slate-200 text-slate-500 uppercase tracking-wider font-bold bg-slate-50/60">
                      <th className="py-3 px-3">No</th>
                      <th className="py-3 px-3">Waktu</th>
                      <th className="py-3 px-3">Nama Siswa</th>
                      <th className="py-3 px-3">Kelas</th>
                      <th className="py-3 px-3">Studi Kasus</th>
                      <th className="py-3 px-3 text-center">Skor</th>
                      <th className="py-3 px-3 text-center">Status</th>
                      <th className="py-3 px-3 text-right no-print">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {filteredSubs.length === 0 ? (
                      <tr>
                        <td colSpan="8" className="py-8 text-center text-slate-400">
                          <p className="font-semibold text-sm">Belum ada riwayat submission siswa.</p>
                          <p className="text-xs mt-1">Siswa dapat mengerjakan diagram di kanvas, atau klik "Isi Data Simulasi Siswa" di atas untuk mencoba.</p>
                        </td>
                      </tr>
                    ) : (
                      filteredSubs.map((sub, idx) => {
                        const isLulus = (sub.score || 0) >= 75;
                        const dateFormatted = sub.created_at
                          ? new Date(sub.created_at).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' })
                          : '-';
                        return (
                          <tr key={sub.id || idx} className="hover:bg-slate-50/70 transition-colors">
                            <td className="py-3 px-3 font-mono text-slate-400">{idx + 1}</td>
                            <td className="py-3 px-3 font-mono text-slate-500 text-[11px] whitespace-nowrap">{dateFormatted}</td>
                            <td className="py-3 px-3 font-bold text-slate-900">{sub.student_name}</td>
                            <td className="py-3 px-3 font-semibold text-slate-600">{sub.class_name}</td>
                            <td className="py-3 px-3">
                              <span className="px-2 py-0.5 rounded-md bg-slate-100 font-mono text-[11px] text-slate-700">
                                {sub.case_key}
                              </span>
                            </td>
                            <td className="py-3 px-3 text-center font-extrabold text-sm">
                              <span className={isLulus ? 'text-emerald-600' : 'text-amber-600'}>
                                {sub.score}
                              </span>
                            </td>
                            <td className="py-3 px-3 text-center">
                              {isLulus ? (
                                <span className="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                  {sub.remedial_success ? 'Tuntas (Remidi)' : 'Tuntas'}
                                </span>
                              ) : sub.is_remedial_open ? (
                                <span className="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-800 border border-blue-200 animate-pulse">
                                  Remidi Dibuka
                                </span>
                              ) : (
                                <span className="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-200">
                                  Remedial
                                </span>
                              )}
                            </td>
                            <td className="py-3 px-3 text-right no-print">
                              <div className="flex items-center justify-end gap-1.5">
                                {!isLulus && (
                                  <button
                                    type="button"
                                    onClick={() => handleToggleRemedial(sub.id || idx)}
                                    className={`px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all cursor-pointer active:scale-95 shadow-2xs flex items-center gap-1 ${
                                      sub.is_remedial_open
                                        ? 'bg-emerald-600 hover:bg-emerald-700 text-white'
                                        : 'bg-amber-500 hover:bg-amber-600 text-white'
                                    }`}
                                    title={sub.is_remedial_open ? 'Klik untuk menutup akses remedial' : 'Klik untuk membuka akses remedial bagi siswa ini'}
                                  >
                                    <span>{sub.is_remedial_open ? '✅ Remidi Terbuka' : '🔓 Buka Remidi'}</span>
                                  </button>
                                )}
                                <button
                                  type="button"
                                  onClick={() => setSelectedSubDetail(sub)}
                                  className="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-[11px] font-bold transition-all cursor-pointer"
                                >
                                  Detail
                                </button>
                              </div>
                            </td>
                          </tr>
                        );
                      })
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        )}

        {/* TAB 2: KELOLA STUDI KASUS */}
        {activeTab === 'studi_kasus' && (
          <div className="space-y-6">
            <div className="bg-gradient-to-r from-indigo-700 via-purple-700 to-indigo-900 rounded-3xl p-6 sm:p-7 text-white shadow-xl relative overflow-hidden">
              <div className="max-w-2xl">
                <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-bold text-indigo-100 mb-2.5">
                  <span>⚙️</span> Modul Manajemen Soal & Mesin Penilaian Otomatis
                </span>
                <h2 className="text-xl sm:text-2xl font-black tracking-tight text-white">
                  Katalog Studi Kasus & Kunci Jawaban Acuan
                </h2>
                <p className="text-xs sm:text-sm text-indigo-100/90 mt-1.5 leading-relaxed">
                  Definisikan soal algoritma baru untuk siswa Anda. Masukkan kata kunci input, formula rumus, atau kondisi percabangan Ya/Tidak. Sistem akan mencocokkan diagram alir siswa secara otomatis dan memberikan skor dari 0 hingga 100 secara instan.
                </p>
              </div>
            </div>

            {/* Grid Kartu Soal */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
              {allCasesList.map((cs) => (
                <div key={cs.id || cs.case_key} className="bg-white rounded-3xl border border-slate-200 shadow-xs hover:shadow-md transition-all p-5 sm:p-6 flex flex-col justify-between">
                  <div className="space-y-3">
                    <div className="flex items-start justify-between gap-3">
                      <div className="flex items-start gap-3">
                        <div className="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-2xl flex-shrink-0">
                          {cs.icon || '📐'}
                        </div>
                        <div>
                          <div className="flex flex-wrap items-center gap-2">
                            <h3 className="text-base font-black text-slate-900">{cs.title}</h3>
                            <span className="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-blue-100 text-blue-800">
                              {cs.badge || (cs.flow_type === 'branching' ? 'Alur Percabangan' : 'Alur Sekuensial')}
                            </span>
                          </div>
                          <div className="flex items-center gap-2 mt-1">
                            <span className="text-[11px] font-mono text-slate-400">key: {cs.id || cs.case_key}</span>
                            {cs.is_default ? (
                              <span className="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-bold">
                                🔒 Bawaan Sistem
                              </span>
                            ) : (
                              <span className="px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 text-[10px] font-bold">
                                ✍️ Buatan Guru
                              </span>
                            )}
                          </div>
                        </div>
                      </div>

                      {!cs.is_default && (
                        <button
                          type="button"
                          onClick={() => handleDeleteCase(cs.id || cs.case_key)}
                          className="px-2.5 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50 rounded-xl transition-all border border-rose-200 cursor-pointer"
                        >
                          Hapus
                        </button>
                      )}
                    </div>

                    {cs.formula && (
                      <div className="p-2.5 bg-slate-50 rounded-xl text-xs font-semibold text-slate-700 border border-slate-200/60">
                        {cs.formula}
                      </div>
                    )}

                    <p className="text-xs text-slate-600 leading-relaxed">
                      {cs.description}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
      </main>

      {/* Modal Detail Submission */}
      {selectedSubDetail && (
        <div className="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 animate-fadeIn">
          <div className="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <div>
                <h3 className="text-base font-black text-slate-900">Detail Hasil Praktikum</h3>
                <p className="text-xs text-slate-500">{selectedSubDetail.student_name} ({selectedSubDetail.class_name})</p>
              </div>
              <button
                type="button"
                onClick={() => setSelectedSubDetail(null)}
                className="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold flex items-center justify-center cursor-pointer"
              >
                ×
              </button>
            </div>

            <div className="flex items-center justify-between p-3.5 bg-slate-50 rounded-2xl">
              <div>
                <span className="text-xs text-slate-500 font-bold uppercase">Studi Kasus:</span>
                <p className="text-sm font-black text-slate-900">{selectedSubDetail.case_key}</p>
              </div>
              <div className="text-right">
                <span className="text-xs text-slate-500 font-bold uppercase">Skor Evaluasi:</span>
                <p className="text-xl font-black text-indigo-600">{selectedSubDetail.score} / 100</p>
              </div>
            </div>

            <div>
              <h4 className="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Catatan Evaluasi Sistem:</h4>
              <div className="space-y-1.5 max-h-60 overflow-y-auto pr-1">
                {Array.isArray(selectedSubDetail.feedback) && selectedSubDetail.feedback.map((fb, i) => (
                  <div
                    key={i}
                    className={`p-2.5 rounded-xl text-xs font-medium border ${
                      fb.includes('✅') || fb.includes('valid') || fb.includes('tepat')
                        ? 'bg-emerald-50/70 border-emerald-200 text-emerald-900'
                        : 'bg-amber-50/70 border-amber-200 text-amber-900'
                    }`}
                  >
                    {fb}
                  </div>
                ))}
              </div>
            </div>

            <button
              type="button"
              onClick={() => setSelectedSubDetail(null)}
              className="w-full py-2.5 bg-slate-100 hover:bg-slate-200 font-bold text-xs text-slate-700 rounded-xl cursor-pointer"
            >
              Tutup
            </button>
          </div>
        </div>
      )}

      {/* Modal Tambah Studi Kasus Baru */}
      {showCreateModal && (
        <div className="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 animate-fadeIn">
          <div className="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-slate-200 space-y-4 max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <div>
                <h3 className="text-base font-black text-slate-900">Tambah Studi Kasus Baru</h3>
                <p className="text-xs text-slate-500">Tentukan soal dan kata kunci evaluasi untuk siswa.</p>
              </div>
              <button
                type="button"
                onClick={() => setShowCreateModal(false)}
                className="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold flex items-center justify-center cursor-pointer"
              >
                ×
              </button>
            </div>

            <form onSubmit={handleCreateCase} className="space-y-3.5 text-xs">
              <div>
                <label className="block font-bold text-slate-700 uppercase mb-1">Judul Soal / Studi Kasus</label>
                <input
                  type="text"
                  required
                  placeholder="Contoh: Menghitung Keliling Persegi"
                  value={newCase.title}
                  onChange={(e) => setNewCase({ ...newCase, title: e.target.value })}
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:bg-white focus:border-indigo-500 outline-none"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-bold text-slate-700 uppercase mb-1">Tipe Alur</label>
                  <select
                    value={newCase.flow_type}
                    onChange={(e) => setNewCase({ ...newCase, flow_type: e.target.value })}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-bold focus:bg-white focus:border-indigo-500 outline-none"
                  >
                    <option value="sequential">Sekuensial (Lurus)</option>
                    <option value="branching">Percabangan (Kondisi Ya/Tidak)</option>
                  </select>
                </div>
                <div>
                  <label className="block font-bold text-slate-700 uppercase mb-1">Icon Simbol (Emoji)</label>
                  <input
                    type="text"
                    value={newCase.icon}
                    onChange={(e) => setNewCase({ ...newCase, icon: e.target.value })}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-center font-bold text-base focus:bg-white focus:border-indigo-500 outline-none"
                  />
                </div>
              </div>

              <div>
                <label className="block font-bold text-slate-700 uppercase mb-1">Rumus / Petunjuk Ringkas</label>
                <input
                  type="text"
                  placeholder="Contoh: Rumus: Keliling = 4 × Sisi"
                  value={newCase.formula}
                  onChange={(e) => setNewCase({ ...newCase, formula: e.target.value })}
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:bg-white focus:border-indigo-500 outline-none"
                />
              </div>

              <div>
                <label className="block font-bold text-slate-700 uppercase mb-1">Deskripsi Soal untuk Siswa</label>
                <textarea
                  rows="2"
                  placeholder="Rancang flowchart yang membaca masukan..."
                  value={newCase.description}
                  onChange={(e) => setNewCase({ ...newCase, description: e.target.value })}
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:bg-white focus:border-indigo-500 outline-none"
                />
              </div>

              <div>
                <label className="block font-bold text-slate-700 uppercase mb-1">Kata Kunci Input (Pisahkan dengan koma)</label>
                <input
                  type="text"
                  placeholder="Contoh: sisi, s, panjang"
                  value={newCase.expected_inputs}
                  onChange={(e) => setNewCase({ ...newCase, expected_inputs: e.target.value })}
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:bg-white focus:border-indigo-500 outline-none"
                />
              </div>

              {newCase.flow_type === 'sequential' ? (
                <>
                  <div>
                    <label className="block font-bold text-slate-700 uppercase mb-1">Kata Kunci Rumus / Proses</label>
                    <input
                      type="text"
                      placeholder="Contoh: 4 * sisi, keliling"
                      value={newCase.expected_process}
                      onChange={(e) => setNewCase({ ...newCase, expected_process: e.target.value })}
                      className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:bg-white focus:border-indigo-500 outline-none"
                    />
                  </div>
                  <div>
                    <label className="block font-bold text-slate-700 uppercase mb-1">Kata Kunci Output</label>
                    <input
                      type="text"
                      placeholder="Contoh: cetak, hasil, keliling"
                      value={newCase.expected_outputs}
                      onChange={(e) => setNewCase({ ...newCase, expected_outputs: e.target.value })}
                      className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:bg-white focus:border-indigo-500 outline-none"
                    />
                  </div>
                </>
              ) : (
                <>
                  <div>
                    <label className="block font-bold text-slate-700 uppercase mb-1">Syarat Kondisi Keputusan</label>
                    <input
                      type="text"
                      placeholder="Contoh: Nilai >= 75?"
                      value={newCase.expected_decision}
                      onChange={(e) => setNewCase({ ...newCase, expected_decision: e.target.value })}
                      className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:bg-white focus:border-indigo-500 outline-none"
                    />
                  </div>
                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="block font-bold text-slate-700 uppercase mb-1">Teks Output Cabang [Ya]</label>
                      <input
                        type="text"
                        placeholder="Contoh: Lulus"
                        value={newCase.expected_yes_output}
                        onChange={(e) => setNewCase({ ...newCase, expected_yes_output: e.target.value })}
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:bg-white focus:border-indigo-500 outline-none"
                      />
                    </div>
                    <div>
                      <label className="block font-bold text-slate-700 uppercase mb-1">Teks Output Cabang [Tidak]</label>
                      <input
                        type="text"
                        placeholder="Contoh: Remedial"
                        value={newCase.expected_no_output}
                        onChange={(e) => setNewCase({ ...newCase, expected_no_output: e.target.value })}
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:bg-white focus:border-indigo-500 outline-none"
                      />
                    </div>
                  </div>
                </>
              )}

              <div className="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="px-4 py-2 bg-slate-100 hover:bg-slate-200 font-bold text-slate-700 rounded-xl cursor-pointer"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl cursor-pointer shadow-md shadow-indigo-600/20"
                >
                  Simpan Studi Kasus
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

// ============================================================================
// WIDGET UTAMA HOSTING STATIS (GITHUB PAGES)
// ============================================================================
function StaticAppContainer() {
  const [currentUser, setCurrentUser] = useState(() => {
    try {
      const saved = localStorage.getItem('flowchart_auth_user');
      if (saved) {
        const parsed = JSON.parse(saved);
        if (
          parsed?.name?.includes('Budi Santoso') ||
          parsed?.name?.includes('Aditya Pratama') ||
          isBlockedStudent(parsed?.name)
        ) {
          localStorage.removeItem('flowchart_auth_user');
          return null;
        }
        return parsed;
      }
      return null;
    } catch (e) {
      return null;
    }
  });

  const [currentView, setCurrentView] = useState(() => {
    if (!currentUser) return 'login';
    return currentUser.role === 'guru' ? 'guru' : 'flowchart';
  });

  const [caseVersion, setCaseVersion] = useState(0);

  const handleLogin = (user) => {
    setCurrentUser(user);
    try {
      localStorage.setItem('flowchart_auth_user', JSON.stringify(user));
    } catch (e) {}
    setCurrentView(user.role === 'guru' ? 'guru' : 'flowchart');
  };

  const handleLogout = () => {
    setCurrentUser(null);
    try {
      localStorage.removeItem('flowchart_auth_user');
    } catch (e) {}
    setCurrentView('login');
  };

  if (!currentUser || currentView === 'login') {
    return <LoginForm onLogin={handleLogin} />;
  }

  return (
    <div className="min-h-screen bg-[#F8FAFC] text-slate-800 font-sans antialiased selection:bg-blue-100 selection:text-blue-800">
      {/* Top Navigation Bar */}
      <header className="bg-white/90 backdrop-blur-md border-b border-slate-200 sticky top-0 z-50 shadow-xs">
        <div className="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-2.5 sm:py-0 sm:h-16 flex items-center justify-between gap-2">
          <div className="flex items-center space-x-2.5 sm:space-x-3 min-w-0">
            <img
              src={appLogo}
              alt="Logo Laboratorium Flowchart"
              className="w-10 h-10 sm:w-11 sm:h-11 rounded-xl object-contain shadow-xs flex-shrink-0 bg-white border border-slate-200/80 p-0.5"
            />
            <div className="min-w-0">
              <h1 className="text-sm sm:text-lg font-extrabold text-slate-900 tracking-tight flex items-center gap-1.5">
                <span className="truncate">Laboratorium Flowchart</span>
                {currentUser.role === 'guru' ? (
                  <span className="text-[9px] sm:text-[10px] uppercase font-bold tracking-wider px-1.5 sm:px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 flex-shrink-0">
                    Guru
                  </span>
                ) : (
                  <span className="text-[9px] sm:text-[10px] uppercase font-bold tracking-wider px-1.5 sm:px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 flex-shrink-0">
                    Siswa
                  </span>
                )}
              </h1>
              <p className="text-[11px] sm:text-xs text-slate-500 truncate">
                {currentUser.name} {currentUser.className ? `• ${currentUser.className}` : `(${currentUser.email})`}
              </p>
            </div>
          </div>

          <div className="flex items-center gap-1.5 sm:gap-3 flex-shrink-0">
            {currentUser.role === 'guru' && (
              <>
                <button
                  type="button"
                  onClick={() => setCurrentView('guru')}
                  className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer active:scale-95 ${
                    currentView === 'guru'
                      ? 'bg-indigo-600 text-white shadow-xs'
                      : 'bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200'
                  }`}
                >
                  Portal Guru
                </button>
                <button
                  type="button"
                  onClick={() => setCurrentView('flowchart')}
                  className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer active:scale-95 ${
                    currentView === 'flowchart'
                      ? 'bg-blue-600 text-white shadow-xs'
                      : 'bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200'
                  }`}
                >
                  Kanvas Siswa
                </button>
              </>
            )}

            <button
              type="button"
              onClick={handleLogout}
              className="px-2.5 sm:px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs hover:shadow-md cursor-pointer flex items-center gap-1.5 active:scale-95"
              title="Keluar Akun"
            >
              <svg className="w-3.5 h-3.5 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                <path d="M18.36 6.64a9 9 0 1 1-12.73 0" />
                <line x1="12" y1="2" x2="12" y2="12" />
              </svg>
              <span className="hidden sm:inline">Keluar</span>
            </button>
          </div>
        </div>
      </header>

      {/* Tampilan Halaman */}
      {currentView === 'guru' ? (
        <GuruPortalView
          currentUser={currentUser}
          onOpenCanvas={() => setCurrentView('flowchart')}
          onLogout={handleLogout}
          onCaseStudiesChanged={() => setCaseVersion((v) => v + 1)}
        />
      ) : (
        <main className="max-w-7xl mx-auto px-2.5 sm:px-6 lg:px-8 py-3.5 sm:py-6 space-y-4 sm:space-y-6">
          {/* Welcome Card */}
          <div className="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-7 border border-slate-200/90 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3 sm:gap-4">
            <div className="max-w-2xl">
              <div className="inline-flex items-center gap-1 px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-[10px] sm:text-xs font-bold uppercase tracking-wider mb-2">
                <span>✨</span>
                <span>Ruang Praktik Mandiri Siswa</span>
              </div>
              <h2 className="text-lg sm:text-2xl font-black tracking-tight text-slate-900 mb-1">
                Susun Flowchart, Uji Logika Algoritma, dan Raih Skor Maksimal!
              </h2>
              <p className="text-slate-500 text-xs sm:text-sm leading-relaxed">
                Pilih studi kasus yang ingin dikerjakan di bawah. Rangkai simbol dan panah alir secara mandiri pada kanvas.
              </p>
            </div>
            <div className="flex items-center gap-3 self-start md:self-auto">
              <div className="hidden lg:flex items-center gap-2 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-600">
                <span>💡</span>
                <span>Sistem memeriksa kesesuaian alur secara otomatis</span>
              </div>
            </div>
          </div>

          {/* Panduan Simbol Flowchart Standar (ANSI/ISO) */}
          <div className="bg-white border border-slate-200/90 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-xs">
            <h3 className="text-xs sm:text-sm font-extrabold text-slate-900 flex items-center gap-2 mb-3 sm:mb-4">
              <span>📖</span>
              <span>Panduan Simbol Flowchart Standar (ANSI/ISO)</span>
            </h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3.5">
              <div className="p-3.5 sm:p-4 rounded-2xl bg-amber-50/70 border border-amber-200 text-xs">
                <div className="font-extrabold text-amber-950 flex items-center gap-1.5 mb-1.5">
                  <span className="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block" />
                  <span>Terminator</span>
                </div>
                <p className="text-amber-900 leading-relaxed text-[11px]">
                  Bentuk oval/kapsul. Menandakan awal (<b>Mulai</b>) dan akhir (<b>Selesai</b>) dari alur algoritma.
                </p>
              </div>

              <div className="p-3.5 sm:p-4 rounded-2xl bg-sky-50/70 border border-sky-200 text-xs">
                <div className="font-extrabold text-sky-950 flex items-center gap-1.5 mb-1.5">
                  <span className="w-2.5 h-2.5 bg-sky-500 -skew-x-12 rounded-xs inline-block" />
                  <span>Input / Output (I/O)</span>
                </div>
                <p className="text-sky-900 leading-relaxed text-[11px]">
                  Jajar genjang. Digunakan untuk membaca masukan dari pengguna atau menampilkan hasil/cetakan.
                </p>
              </div>

              <div className="p-3.5 sm:p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200 text-xs">
                <div className="font-extrabold text-emerald-950 flex items-center gap-1.5 mb-1.5">
                  <span className="w-2.5 h-2.5 bg-emerald-500 rounded-xs inline-block" />
                  <span>Proses (Process)</span>
                </div>
                <p className="text-emerald-900 leading-relaxed text-[11px]">
                  Persegi panjang. Digunakan untuk langkah perhitungan rumus matematika atau manipulasi data.
                </p>
              </div>

              <div className="p-3.5 sm:p-4 rounded-2xl bg-orange-50/70 border border-orange-200 text-xs">
                <div className="font-extrabold text-orange-950 flex items-center gap-1.5 mb-1.5">
                  <span className="w-2.5 h-2.5 bg-orange-500 rotate-45 rounded-xs inline-block" />
                  <span>Keputusan (Decision)</span>
                </div>
                <p className="text-orange-900 leading-relaxed text-[11px]">
                  Belah ketupat. Digunakan untuk kondisi logika percabangan yang menghasilkan nilai <b>Ya</b> atau <b>Tidak</b>.
                </p>
              </div>
            </div>
          </div>

          {/* Card Flowchart Editor React */}
          <section className="bg-white rounded-2xl sm:rounded-3xl shadow-xs border border-slate-200 p-2 sm:p-6 transition-all">
            <FlowchartEditor currentUser={currentUser} key={caseVersion} />
          </section>
        </main>
      )}
    </div>
  );
}

// ============================================================================
// ROOT COMPONENT (MEMISAHKAN MODE LARAVEL VS MODE GITHUB PAGES STATIS)
// ============================================================================
function AppRoot() {
  const isLaravel = Boolean(document.querySelector('meta[name="csrf-token"]'));
  if (isLaravel) {
    return <FlowchartEditor />;
  }
  return <StaticAppContainer />;
}

// Inisialisasi Root React
function initFlowchartApp() {
  const container = document.getElementById('flowchart-app') || document.getElementById('flowchart-root');
  if (container && !container.dataset.reactMounted) {
    container.dataset.reactMounted = 'true';
    const root = createRoot(container);
    root.render(<AppRoot />);
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initFlowchartApp);
} else {
  initFlowchartApp();
}
