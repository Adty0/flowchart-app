import React, { useState, useCallback } from 'react';
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

// ============================================================================
// DAFTAR STUDI KASUS & SOAL LATIHAN SISWA (KANVAS SELALU KOSONG AGAR SISWA MENYUSUN SENDIRI)
// ============================================================================
const STUDI_KASUS = {
  luas_segitiga: {
    id: 'luas_segitiga',
    title: 'Studi Kasus 1: Menghitung Luas Segitiga',
    icon: '📐',
    badge: 'Alur Sekuensial',
    formula: 'Rumus: Luas = 0.5 × Alas × Tinggi',
    description: 'Buatlah flowchart yang membaca masukan nilai alas dan tinggi dari pengguna, menghitung luas segitiga dengan rumus matematika, lalu mencetak nilai hasil luas ke layar.',
  },
  kelulusan: {
    id: 'kelulusan',
    title: 'Studi Kasus 2: Cek Kelulusan Nilai Ujian',
    icon: '🎓',
    badge: 'Alur Percabangan',
    formula: 'Kondisi: Jika Nilai ≥ 75 maka LULUS, jika tidak maka REMEDIAL',
    description: 'Buatlah flowchart yang menerima masukan nilai ujian siswa, menguji apakah nilai mencapai batas kriteria (>= 75), lalu mencetak status kelulusannya.',
  },
  bebas: {
    id: 'bebas',
    title: 'Studi Kasus Mandiri: Eksplorasi Bebas',
    icon: '✏️',
    badge: 'Proyek Bebas',
    formula: 'Alur Logika Bebas Sesuai Instruksi Guru',
    description: 'Rancang flowchart secara mandiri sesuai dengan tugas atau studi kasus pemrograman yang diberikan oleh guru pada pertemuan hari ini.',
  },
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
        className="!w-3 !h-3 !bg-amber-500 !border-2 !border-white !shadow-sm"
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
        className="!w-3 !h-3 !bg-amber-500 !border-2 !border-white !shadow-sm"
      />
      <button
        type="button"
        onClick={() => data.onDeleteNode?.(id)}
        className="absolute -top-2 -right-2 w-5 h-5 bg-rose-500 hover:bg-rose-600 text-white rounded-full text-xs font-bold items-center justify-center shadow-md hidden group-hover:flex transition-transform hover:scale-110"
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
        className="!w-3 !h-3 !bg-sky-500 !border-2 !border-white !shadow-sm"
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
        className="!w-3 !h-3 !bg-sky-500 !border-2 !border-white !shadow-sm"
      />
      <button
        type="button"
        onClick={() => data.onDeleteNode?.(id)}
        className="absolute -top-2 -right-2 w-5 h-5 bg-rose-500 hover:bg-rose-600 text-white rounded-full text-xs font-bold items-center justify-center shadow-md hidden group-hover:flex transition-transform hover:scale-110"
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
        className="!w-3 !h-3 !bg-emerald-500 !border-2 !border-white !shadow-sm"
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
        className="!w-3 !h-3 !bg-emerald-500 !border-2 !border-white !shadow-sm"
      />
      <button
        type="button"
        onClick={() => data.onDeleteNode?.(id)}
        className="absolute -top-2 -right-2 w-5 h-5 bg-rose-500 hover:bg-rose-600 text-white rounded-full text-xs font-bold items-center justify-center shadow-md hidden group-hover:flex transition-transform hover:scale-110"
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
        className="!w-3 !h-3 !bg-orange-500 !border-2 !border-white !shadow-sm !top-1"
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
          placeholder="Kondisi?"
          className="w-24 text-center bg-transparent border-b border-transparent hover:border-orange-300 focus:border-orange-500 font-bold text-xs text-orange-950 outline-none py-0.5 mt-0.5"
        />
      </div>

      <Handle
        type="source"
        position={Position.Left}
        id="left"
        className="!w-3 !h-3 !bg-orange-500 !border-2 !border-white !shadow-sm !left-1"
      />
      <Handle
        type="source"
        position={Position.Right}
        id="right"
        className="!w-3 !h-3 !bg-orange-500 !border-2 !border-white !shadow-sm !right-1"
      />
      <Handle
        type="source"
        position={Position.Bottom}
        id="bottom"
        className="!w-3 !h-3 !bg-orange-500 !border-2 !border-white !shadow-sm !bottom-1"
      />

      <button
        type="button"
        onClick={() => data.onDeleteNode?.(id)}
        className="absolute top-1 right-1 w-5 h-5 bg-rose-500 hover:bg-rose-600 text-white rounded-full text-xs font-bold items-center justify-center shadow-md hidden group-hover:flex transition-transform hover:scale-110 z-10"
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
// KOMPONEN UTAMA EDITOR FLOWCHART SISWA
// ============================================================================
export default function FlowchartEditor() {
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

  const [selectedCaseKey, setSelectedCaseKey] = useState('luas_segitiga');

  // KANVAS DIMULAI DALAM KONDISI KOSONG AGAR SISWA MENYUSUN SENDIRI DARI AWAL
  const [nodes, setNodes, onNodesChange] = useNodesState([]);
  const [edges, setEdges, onEdgesChange] = useEdgesState([]);

  const [assessment, setAssessment] = useState(null);
  const [loading, setLoading] = useState(false);
  const [toast, setToast] = useState(null);

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
      setEdges((prev) =>
        addEdge(
          {
            ...params,
            animated: true,
            style: { stroke: '#2563eb', strokeWidth: 2 },
            markerEnd: { type: MarkerType.ArrowClosed, color: '#2563eb' },
          },
          prev
        )
      );
      showToast('Garis alir panah berhasil disambungkan! 🔗', 'success');
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

    if (nodes.length > 0) {
      if (confirm(`Pindah ke "${STUDI_KASUS[key].title}"? Kanvas akan dikosongkan agar Anda dapat mengerjakan soal baru dari awal.`)) {
        setNodes([]);
        setEdges([]);
        setAssessment(null);
      } else {
        return;
      }
    }

    setSelectedCaseKey(key);
    showToast(`Studi kasus "${STUDI_KASUS[key].title}" dipilih. Silakan rangkai flowchart Anda!`, 'info');
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
      diagram_data: {
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

      const data = await res.json();
      if (res.ok) {
        setAssessment({
          score: data.score,
          feedback: data.feedback,
          id: data.submission_id,
        });
        showToast(`🎉 Evaluasi selesai! Skor Anda: ${data.score}/100`, 'success');
      } else {
        alert('Gagal mengevaluasi: ' + (data.message || 'Terjadi galat'));
      }
    } catch (err) {
      console.error(err);
      alert('Gagal terhubung ke server Laravel.');
    } finally {
      setLoading(false);
    }
  };

  const currentCase = STUDI_KASUS[selectedCaseKey] || STUDI_KASUS.luas_segitiga;

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
      <div className="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/90 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        {/* Identitas Siswa & Kelas */}
        <div className="flex flex-wrap items-center gap-3 flex-1 min-w-[280px]">
          <div className="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center font-bold text-lg flex-shrink-0">
            👤
          </div>
          <div className="flex-1 min-w-[170px]">
            <label className="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-0.5">Nama Siswa</label>
            <input
              type="text"
              value={studentName}
              onChange={handleNameChange}
              placeholder="Ketik Nama Anda..."
              className="w-full px-3 py-1.5 bg-slate-50/70 border border-slate-200 rounded-lg text-sm text-slate-800 font-bold focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
            />
          </div>
          <div className="w-32">
            <label className="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-0.5">Kelas / Rombel</label>
            <input
              type="text"
              value={studentClass}
              onChange={handleClassChange}
              placeholder="10 RPL 1"
              className="w-full px-3 py-1.5 bg-indigo-50/60 border border-indigo-200 rounded-lg text-sm text-indigo-900 font-extrabold focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all"
            />
          </div>
        </div>

        {/* Tab Pilihan Studi Kasus */}
        <div className="flex flex-wrap items-center gap-2">
          <span className="text-xs font-bold text-slate-500 mr-1 flex items-center gap-1">
            <span>📚</span>
            <span>Pilih Kasus Soal:</span>
          </span>
          <button
            type="button"
            onClick={() => handleSelectCase('luas_segitiga')}
            className={`px-3 py-1.5 rounded-xl text-xs font-bold border transition-all cursor-pointer ${
              selectedCaseKey === 'luas_segitiga'
                ? 'bg-blue-600 border-blue-600 text-white shadow-sm'
                : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'
            }`}
          >
            📐 Hitung Luas
          </button>
          <button
            type="button"
            onClick={() => handleSelectCase('kelulusan')}
            className={`px-3 py-1.5 rounded-xl text-xs font-bold border transition-all cursor-pointer ${
              selectedCaseKey === 'kelulusan'
                ? 'bg-blue-600 border-blue-600 text-white shadow-sm'
                : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'
            }`}
          >
            🎓 Cek Kelulusan
          </button>
          <button
            type="button"
            onClick={() => handleSelectCase('bebas')}
            className={`px-3 py-1.5 rounded-xl text-xs font-bold border transition-all cursor-pointer ${
              selectedCaseKey === 'bebas'
                ? 'bg-blue-600 border-blue-600 text-white shadow-sm'
                : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'
            }`}
          >
            ✏️ Mandiri (Bebas)
          </button>
        </div>
      </div>

      {/* KARTU PETUNJUK SOAL STUDI KASUS (PANDUAN SISWA) */}
      <div className="bg-gradient-to-r from-blue-50/70 via-indigo-50/40 to-sky-50/60 p-4 sm:p-5 rounded-2xl border border-blue-200 shadow-2xs">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-blue-200/60 mb-3">
          <div className="flex items-center gap-2">
            <span className="text-xl">{currentCase.icon}</span>
            <div>
              <h3 className="text-sm font-extrabold text-blue-950 flex items-center gap-2">
                <span>{currentCase.title}</span>
                <span className="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 border border-blue-200">
                  {currentCase.badge}
                </span>
              </h3>
              <p className="text-xs text-blue-700 font-semibold mt-0.5">{currentCase.formula}</p>
            </div>
          </div>
          <span className="text-[11px] font-bold text-slate-500 bg-white px-3 py-1 rounded-full border border-slate-200 self-start sm:self-auto">
            Kanvas Kosong: Rangkai flowchart sendiri
          </span>
        </div>

        <p className="text-xs text-slate-700 leading-relaxed">
          <b>Instruksi Soal:</b> {currentCase.description}
        </p>
      </div>

      {/* PALET SIMBOL DENGAN WARNA SESUAI ASLINYA & TOMBOL AKSI */}
      <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-3">
        {/* Palet Simbol */}
        <div className="flex flex-wrap items-center gap-2">
          <span className="text-xs font-bold text-slate-500 uppercase tracking-wider mr-1 flex items-center gap-1">
            <span>🧩</span> Tambah Simbol:
          </span>

          {/* 1. Terminator */}
          <button
            type="button"
            onClick={() => addSymbol('terminator', 'Mulai')}
            className="flex items-center gap-2 px-3 py-2 bg-amber-100 hover:bg-amber-200 text-amber-950 border border-amber-300 rounded-xl text-xs font-bold shadow-2xs transition-all active:scale-95 cursor-pointer"
            title="Terminator: Penanda Mulai atau Selesai"
          >
            <span className="w-3 h-3 rounded-full bg-amber-500 inline-block" />
            <span>Terminator (Mulai/Selesai)</span>
          </button>

          {/* 2. I/O */}
          <button
            type="button"
            onClick={() => addSymbol('io', 'Input Data')}
            className="flex items-center gap-2 px-3 py-2 bg-sky-100 hover:bg-sky-200 text-sky-950 border border-sky-300 rounded-xl text-xs font-bold shadow-2xs transition-all active:scale-95 cursor-pointer"
            title="I/O: Masukan atau Luaran data"
          >
            <span className="w-3 h-3 bg-sky-500 -skew-x-12 inline-block rounded-xs" />
            <span>I/O (Input/Output)</span>
          </button>

          {/* 3. Proses */}
          <button
            type="button"
            onClick={() => addSymbol('process', 'Hitung Data')}
            className="flex items-center gap-2 px-3 py-2 bg-emerald-100 hover:bg-emerald-200 text-emerald-950 border border-emerald-300 rounded-xl text-xs font-bold shadow-2xs transition-all active:scale-95 cursor-pointer"
            title="Proses: Perhitungan rumus atau operasi"
          >
            <span className="w-3 h-3 bg-emerald-500 inline-block rounded-xs" />
            <span>Proses (Perhitungan)</span>
          </button>

          {/* 4. Keputusan */}
          <button
            type="button"
            onClick={() => addSymbol('decision', 'Kondisi ?')}
            className="flex items-center gap-2 px-3 py-2 bg-orange-100 hover:bg-orange-200 text-orange-950 border border-orange-300 rounded-xl text-xs font-bold shadow-2xs transition-all active:scale-95 cursor-pointer"
            title="Keputusan: Percabangan kondisi Ya/Tidak"
          >
            <span className="w-3 h-3 bg-orange-500 rotate-45 inline-block rounded-xs" />
            <span>Keputusan (Percabangan)</span>
          </button>
        </div>

        {/* Tombol Aksi */}
        <div className="flex items-center gap-2 ml-auto">
          <button
            type="button"
            onClick={handleClearCanvas}
            className="px-3.5 py-2 bg-slate-50 hover:bg-rose-50 text-rose-600 hover:text-rose-700 border border-rose-200 rounded-xl text-xs font-bold shadow-2xs transition-all cursor-pointer"
            title="Kosongkan Kanvas"
          >
            🗑️ Kosongkan Kanvas
          </button>

          <button
            type="button"
            onClick={handleValidateAndSave}
            disabled={loading}
            className="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition-all active:scale-95 disabled:opacity-50 cursor-pointer"
          >
            {loading ? (
              <>
                <span className="animate-spin">⏳</span>
                <span>Memeriksa Alur...</span>
              </>
            ) : (
              <>
                <span>🚀</span>
                <span>Periksa & Simpan ke DB</span>
              </>
            )}
          </button>
        </div>
      </div>

      {/* KANVAS EDITOR DENGAN TAMPILAN BERSIH & MODERN */}
      <div className="relative w-full h-[580px] bg-[#F8FAFC] border-2 border-slate-200 rounded-3xl overflow-hidden shadow-xs">
        <ReactFlow
          nodes={nodesWithHandlers}
          edges={edges}
          onNodesChange={onNodesChange}
          onEdgesChange={onEdgesChange}
          onConnect={onConnect}
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
            className="!rounded-2xl !border !border-slate-300 !bg-white/90"
          />
          <Controls className="!bg-white !rounded-2xl !border !border-slate-200 !shadow-sm" />
          <Background color="#cbd5e1" gap={20} size={1.5} />
        </ReactFlow>

        {/* Petunjuk Kanvas Kosong (Muncul jika kanvas belum ada simbol) */}
        {nodes.length === 0 && (
          <div className="absolute inset-0 pointer-events-none flex flex-col items-center justify-center p-6 text-center">
            <div className="w-14 h-14 rounded-2xl bg-white/90 border border-slate-200 flex items-center justify-center text-2xl shadow-xs mb-3">
              ✏️
            </div>
            <h4 className="text-sm font-extrabold text-slate-700">Kanvas Masih Kosong</h4>
            <p className="text-xs text-slate-500 max-w-sm mt-1">
              Klik tombol warna-warni di atas untuk menambahkan simbol <b>Terminator</b>, <b>I/O</b>, <b>Proses</b>, atau <b>Keputusan</b>, lalu tarik garis panah untuk menyambungkannya.
            </p>
          </div>
        )}

        <div className="absolute top-3 left-3 bg-white/95 backdrop-blur-xs px-3 py-1.5 rounded-xl border border-slate-200 shadow-2xs text-[11px] text-slate-600 pointer-events-none flex items-center gap-1.5">
          <span>💡 <b>Tips:</b> Tarik lingkaran kecil pada simbol untuk menghubungkan panah alur.</span>
        </div>
      </div>

      {/* PANEL HASIL PENILAIAN & EVALUASI OTOMATIS */}
      {assessment && (
        <div className="bg-white border-2 border-blue-200 rounded-3xl p-6 shadow-sm animate-fadeIn">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-3">
            <div className="flex items-center gap-3">
              <div
                className={`w-14 h-14 rounded-2xl flex items-center justify-center text-xl font-black shadow-md ${
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
                <h3 className="text-base font-extrabold text-slate-900">
                  {assessment.score === 100
                    ? '🌟 Luar Biasa! Diagram Sangat Sempurna'
                    : assessment.score >= 80
                    ? '👍 Bagus Sekali! Struktur Alur Sudah Tepat'
                    : assessment.score >= 60
                    ? '✏️ Cukup Baik, Ada Beberapa Catatan Perbaikan'
                    : '💪 Masih Perlu Dilengkapi, Perhatikan Catatan di Bawah'}
                </h3>
                <p className="text-xs text-slate-500 mt-0.5">
                  Tersimpan di Database (Submission #{assessment.id}) • {studentName} ({studentClass})
                </p>
              </div>
            </div>

            <div className="text-right">
              <span className="inline-block text-xs font-black px-3.5 py-1.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-full">
                Skor Akhir: {assessment.score} / 100
              </span>
            </div>
          </div>

          <div className="mt-4">
            <h4 className="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2.5">
              Umpan Balik Evaluasi Logika Flowchart:
            </h4>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-2.5">
              {assessment.feedback.map((item, idx) => {
                const isPositive = item.includes('✅') || item.includes('tersedia') || item.includes('valid') || item.includes('disertakan');
                return (
                  <div
                    key={idx}
                    className={`p-3 rounded-xl border text-xs font-semibold flex items-start gap-2.5 ${
                      isPositive
                        ? 'bg-emerald-50/70 border-emerald-200 text-emerald-900'
                        : 'bg-amber-50/70 border-amber-200 text-amber-900'
                    }`}
                  >
                    <span className="text-sm">{isPositive ? '✅' : '⚠️'}</span>
                    <span className="mt-0.5">{item.replace(/^[✅⚠️]\s*/, '')}</span>
                  </div>
                );
              })}
            </div>
          </div>
        </div>
      )}

      {/* PANDUAN SIMBOL FLOWCHART DENGAN DESAIN BERSIH */}
      <div className="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs">
        <h4 className="text-sm font-extrabold text-slate-900 flex items-center gap-2 mb-4">
          <span>📖</span>
          <span>Panduan Simbol Flowchart Standar (ANSI/ISO)</span>
        </h4>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
          <div className="p-4 rounded-2xl bg-amber-50/70 border border-amber-200 text-xs">
            <div className="font-extrabold text-amber-900 flex items-center gap-1.5 mb-1.5">
              <span className="w-2.5 h-2.5 rounded-full bg-amber-500" />
              <span>Terminator</span>
            </div>
            <p className="text-amber-800 leading-relaxed text-[11px]">
              Bentuk oval/kapsul. Menandakan awal (<b>Mulai</b>) dan akhir (<b>Selesai</b>) dari alur algoritma.
            </p>
          </div>

          <div className="p-4 rounded-2xl bg-sky-50/70 border border-sky-200 text-xs">
            <div className="font-extrabold text-sky-950 flex items-center gap-1.5 mb-1.5">
              <span className="w-2.5 h-2.5 bg-sky-500 -skew-x-12 rounded-xs" />
              <span>Input / Output (I/O)</span>
            </div>
            <p className="text-sky-900 leading-relaxed text-[11px]">
              Jajar genjang. Digunakan untuk membaca masukan dari pengguna atau menampilkan hasil/cetakan.
            </p>
          </div>

          <div className="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200 text-xs">
            <div className="font-extrabold text-emerald-950 flex items-center gap-1.5 mb-1.5">
              <span className="w-2.5 h-2.5 bg-emerald-500 rounded-xs" />
              <span>Proses (Process)</span>
            </div>
            <p className="text-emerald-900 leading-relaxed text-[11px]">
              Persegi panjang. Digunakan untuk langkah perhitungan rumus matematika atau manipulasi data.
            </p>
          </div>

          <div className="p-4 rounded-2xl bg-orange-50/70 border border-orange-200 text-xs">
            <div className="font-extrabold text-orange-950 flex items-center gap-1.5 mb-1.5">
              <span className="w-2.5 h-2.5 bg-orange-500 rotate-45 rounded-xs" />
              <span>Keputusan (Decision)</span>
            </div>
            <p className="text-orange-900 leading-relaxed text-[11px]">
              Belah ketupat. Digunakan untuk kondisi logika percabangan yang menghasilkan nilai <b>Ya</b> atau <b>Tidak</b>.
            </p>
          </div>
        </div>
      </div>

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
