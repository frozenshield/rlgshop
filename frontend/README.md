# ⚡ RLG Online Shop - Pokémon Toys & TCG (Vue 3 + TypeScript)

A vibrant, modern, and playful e-commerce web application for **RLG Online Shop**, themed around **Pokémon Toys, TCG Cards, Figures, Mega Building Sets, and Trainer Gear**!

Built from scratch with **Vue 3 (Composition API)**, **TypeScript**, **Pinia**, **Vue Router 4**, **Tailwind CSS**, **Yup**, and **@vueuse/core**.

Strictly designed following the user's **domain-driven modular feature-sliced architecture** (`src/modules/<feature-name>/`).

---

## 🎮 Features

- 🔴 **Official Pokémon Branding**: Custom Pokéball SVG logo, electric glows, Pokémon Center aesthetic, Poké-type pill tags (Electric, Fire, Dragon, Normal, etc.).
- 🎒 **Trainer Poké-Bag (Cart Drawer)**: Slide-over drawer with free shipping progress bar ($50 milestone), quantity adjustments, and persistent storage.
- ⚡ **Trainer Promo Codes**:
  - `PIKACHU10` (10% off)
  - `POKEBALL20` (20% off)
  - `MASTERBALL` (25% off)
- 🎯 **Poké-Match Gift Advisor**: Interactive 3-step quiz matching trainer rank, favorite Pokémon specialty, and budget to top recommended items.
- 📦 **Express Poké-Checkout**: Form with Yup validation, delivery speeds (Standard Pokédex Dispatch, Pidgey Express Air, Premier Ball Gift Box), and celebratory order confirmation modal.
- 💖 **Trainer Wishlist**: Save favorite Pokémon items to a personal collection and transfer them directly into the bag.
- 🔍 **Live Poké-Search & Filters**: Instant search by Pokémon name, price slider, age/rank filter chips, and sorting.

---

## 🛠️ How to Run the Project

Inside your `rlgshop` directory (`C:\Users\User\Downloads\frontend\rlgshop`) in your terminal:

1. **Install dependencies**:
   ```bash
   npm install
   ```

2. **Start development server**:
   ```bash
   npm run dev
   ```

3. **Run unit tests**:
   ```bash
   npm run test:unit
   ```

4. **Type check & build for production**:
   ```bash
   npm run type-check
   npm run build
   ```
