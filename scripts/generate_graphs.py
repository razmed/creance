#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
generate_graphs.py
Usage:
  python3 generate_graphs.py input.json output_dir

input.json: list d'objets JSON, chaque objet doit contenir au minimum les champs:
  "REGION","SECTEUR","NATURE","MONTANT CREANCE","PROVISION 2024"
(les clés doivent correspondre aux noms des colonnes de ton UI / export PDF)

Sorties:
  output_dir/bar_chart.png
  output_dir/pie_chart.png
  output_dir/radar_chart.png (si possible)
Le script écrit en stdout un JSON {"bar":"...","pie":"...","radar":"..."}
"""

import sys, os, json, io
import math
from collections import defaultdict

# Matplotlib sans affichage
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import numpy as np

def format_value(val):
    """Formatting adaptatif (inspiré de ton code)"""
    try:
        v = float(val)
    except Exception:
        return str(val)
    if abs(v) >= 1e9:
        return f"{v/1e9:.2f}B" if v/1e9 < 10 else f"{v/1e9:.1f}B"
    if abs(v) >= 1e6:
        return f"{v/1e6:.2f}M" if v/1e6 < 10 else f"{v/1e6:.1f}M"
    if abs(v) >= 1e3:
        return f"{v/1e3:.2f}K" if v/1e3 < 10 else f"{v/1e3:.1f}K"
    return f"{v:.2f}" if v < 10 else f"{v:.1f}" if v < 100 else f"{v:.0f}"

def gen_bar_chart(rows, outpath):
    # Agrégation par REGION
    regions = defaultdict(lambda: {'creances':0.0, 'provisions':0.0})
    for r in rows:
        region = r.get("REGION","(Sans région)")
        try:
            creance = float(r.get("MONTANT CREANCE", 0) or 0)
            prov = float(r.get("PROVISION 2024", 0) or 0)
        except:
            creance = 0.0; prov = 0.0
        regions[region]['creances'] += creance
        regions[region]['provisions'] += prov

    if not regions:
        return None

    keys = list(regions.keys())
    creances = [regions[k]['creances'] for k in keys]
    provisions = [regions[k]['provisions'] for k in keys]

    x = np.arange(len(keys))
    width = 0.35

    fig, ax = plt.subplots(figsize=(12,6))
    ax.bar(x - width/2, creances, width, label='Créances', alpha=0.85)
    ax.bar(x + width/2, provisions, width, label='Provisions', alpha=0.85)
    ax.set_xticks(x)
    ax.set_xticklabels(keys, rotation=45, ha='right')
    ax.set_title('Analyse par Région - Créances vs Provisions')
    ax.legend()
    from matplotlib.ticker import FuncFormatter
    ax.yaxis.set_major_formatter(FuncFormatter(lambda y, _: format_value(y)))
    ax.grid(alpha=0.3)
    plt.tight_layout()
    fig.savefig(outpath, dpi=200)
    plt.close(fig)
    return outpath

def gen_pie_chart(rows, outpath):
    # Agrégation par SECTEUR (par montant créance)
    secteurs = defaultdict(float)
    for r in rows:
        secteur = r.get("SECTEUR", "(Sans secteur)")
        try:
            cre = float(r.get("MONTANT CREANCE", 0) or 0)
        except:
            cre = 0.0
        secteurs[secteur] += cre

    if not secteurs:
        return None

    labels = list(secteurs.keys())
    sizes = list(secteurs.values())

    fig, ax = plt.subplots(figsize=(8,8))
    wedges, texts, autotexts = ax.pie(sizes, labels=labels, autopct='%1.1f%%', startangle=90)
    for t in autotexts:
        t.set_color('white')
        t.set_fontweight('bold')
    ax.set_title('Répartition des Créances par Secteur')
    plt.tight_layout()
    fig.savefig(outpath, dpi=200)
    plt.close(fig)
    return outpath

def gen_radar_chart(rows, outpath):
    # Agrégation par NATURE (créances/provisions)
    natures = defaultdict(lambda: {'creances':0.0, 'provisions':0.0})
    for r in rows:
        nat = r.get("NATURE", "(Sans nature)")
        try:
            cr = float(r.get("MONTANT CREANCE", 0) or 0)
            pr = float(r.get("PROVISION 2024", 0) or 0)
        except:
            cr = pr = 0.0
        natures[nat]['creances'] += cr
        natures[nat]['provisions'] += pr

    if len(natures) < 3:
        return None

    keys = list(natures.keys())
    creances = [natures[k]['creances'] for k in keys]
    provisions = [natures[k]['provisions'] for k in keys]
    maxv = max(max(creances), max(provisions)) or 1.0

    # normalization
    cre_n = [c/maxv for c in creances]
    prov_n = [p/maxv for p in provisions]

    N = len(keys)
    angles = np.linspace(0, 2*np.pi, N, endpoint=False).tolist()
    angles += angles[:1]
    cre_r = cre_n + cre_n[:1]
    prov_r = prov_n + prov_n[:1]

    fig = plt.figure(figsize=(8,8))
    ax = fig.add_subplot(111, polar=True)
    ax.plot(angles, cre_r, 'o-', linewidth=2, label='Créances')
    ax.fill(angles, cre_r, alpha=0.25)
    ax.plot(angles, prov_r, 'o-', linewidth=2, label='Provisions')
    ax.fill(angles, prov_r, alpha=0.25)
    ax.set_xticks(angles[:-1])
    ax.set_xticklabels(keys)
    ax.set_ylim(0, 1.1)
    from matplotlib.ticker import FuncFormatter
    ax.yaxis.set_major_formatter(FuncFormatter(lambda y, _: format_value(y*maxv)))
    ax.set_title('Spider Radar par Nature - Créances et Provisions', pad=20)
    ax.legend(loc='upper right')
    plt.tight_layout()
    fig.savefig(outpath, dpi=200)
    plt.close(fig)
    return outpath

def main():
    if len(sys.argv) < 3:
        print(json.dumps({"error":"Usage: generate_graphs.py input.json output_dir"}))
        sys.exit(2)

    input_json = sys.argv[1]
    out_dir = sys.argv[2]

    if not os.path.exists(input_json):
        print(json.dumps({"error":"input file not found"}))
        sys.exit(3)
    try:
        os.makedirs(out_dir, exist_ok=True)
    except Exception as e:
        print(json.dumps({"error":"cannot create output dir: "+str(e)}))
        sys.exit(4)

    with open(input_json, 'r', encoding='utf-8') as f:
        rows = json.load(f)

    # Standardiser les clefs si nécessaire (si les lignes sont tableaux indexés, on attend des dicts)
    if isinstance(rows, list) and rows and isinstance(rows[0], list):
        # Si format ancien liste (ui_list), on peut essayer de le convertir: (optionnel)
        # ici on préfère échouer proprement
        print(json.dumps({"error":"input must be list of dicts with keys like 'REGION','SECTEUR','NATURE','MONTANT CREANCE','PROVISION 2024'"}))
        sys.exit(5)

    out = {}
    bar_p = os.path.join(out_dir, 'bar_chart.png')
    pie_p = os.path.join(out_dir, 'pie_chart.png')
    radar_p = os.path.join(out_dir, 'radar_chart.png')

    try:
        out['bar'] = gen_bar_chart(rows, bar_p) or None
        out['pie'] = gen_pie_chart(rows, pie_p) or None
        out['radar'] = gen_radar_chart(rows, radar_p) or None
    except Exception as e:
        import traceback
        tb = traceback.format_exc()
        print(json.dumps({"error":"exception","message":str(e),"trace":tb}))
        sys.exit(6)

    print(json.dumps(out))
    sys.exit(0)

if __name__ == '__main__':
    main()
