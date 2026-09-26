/**
 * Safely escapes HTML special characters to prevent XSS.
 */
function escapeHtml(str: string): string {
  return str
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

/**
 * Returns an icon emoji corresponding to common section header titles.
 */
function getHeaderIcon(header: string): string {
  const upper = header.toUpperCase();
  if (
    upper.includes("OVERVIEW") ||
    upper.includes("ABOUT") ||
    upper.includes("INTRODUCTION")
  ) {
    return "📦";
  }
  if (
    upper.includes("HIGHLIGHT") ||
    upper.includes("FEATURE") ||
    upper.includes("APPEAL")
  ) {
    return "✨";
  }
  if (
    upper.includes("SPECIFICATION") ||
    upper.includes("SPEC") ||
    upper.includes("DETAIL") ||
    upper.includes("SET DETAILS")
  ) {
    return "⚡";
  }
  if (
    upper.includes("BOX CONTENT") ||
    upper.includes("PACKAGE CONTENT") ||
    upper.includes("INCLUDED") ||
    upper.includes("CONTENTS")
  ) {
    return "🎁";
  }
  if (
    upper.includes("AUTHENTICITY") ||
    upper.includes("CONDITION") ||
    upper.includes("QUALITY") ||
    upper.includes("VERIFIED")
  ) {
    return "🛡️";
  }
  if (
    upper.includes("WARNING") ||
    upper.includes("SAFETY") ||
    upper.includes("NOTE")
  ) {
    return "⚠️";
  }
  return "📌";
}

/**
 * Checks if a string looks like a section heading.
 * e.g. "OVERVIEW", "KEY FEATURES:", "SPECIFICATIONS", "BOX CONTENTS:"
 */
function isSectionHeader(line: string): boolean {
  const trimmed = line.trim();
  if (!trimmed || trimmed.length < 3 || trimmed.length > 50) return false;

  // Header ending with colon e.g. "Key Features:" or "Overview:"
  if (/^[A-Za-z0-9\s&—\-_]+:$/.test(trimmed)) return true;

  // Uppercase block heading
  if (/^[A-Z0-9\s&—\-_/]{3,45}$/.test(trimmed)) {
    const commonHeaders = [
      "OVERVIEW",
      "KEY FEATURES",
      "FEATURES",
      "HIGHLIGHTS",
      "KEY HIGHLIGHTS",
      "SPECIFICATIONS",
      "PRODUCT SPECIFICATIONS",
      "DETAILS",
      "SET DETAILS",
      "BOX CONTENTS",
      "PACKAGE CONTENTS",
      "WHAT'S INCLUDED",
      "AUTHENTICITY",
      "COLLECTOR NOTES",
      "SHIPPING & HANDLING",
    ];
    return (
      commonHeaders.some((h) => trimmed.includes(h)) ||
      trimmed.split(" ").length <= 4
    );
  }

  return false;
}

/**
 * Formats a plain text product description (with tabs and spacing) into HTML with styled icons and lists.
 */
export function formatProductDescription(
  rawDescription?: string | null,
): string {
  if (!rawDescription || !rawDescription.trim()) {
    return "<p class='text-slate-400 italic text-xs'>No product description provided.</p>";
  }

  // If already contains structured HTML tags (e.g. legacy stored descriptions), return as is
  if (/<(h[1-6]|ul|ol|li|div|p|span)[^>]*>/i.test(rawDescription)) {
    return rawDescription;
  }

  const lines = rawDescription.split(/\r?\n/);
  const output: string[] = [];
  let currentList: string[] = [];

  const flushList = () => {
    if (currentList.length > 0) {
      output.push(
        `<ul class="space-y-1.5 my-2 pl-1">${currentList.join("")}</ul>`,
      );
      currentList = [];
    }
  };

  for (let i = 0; i < lines.length; i++) {
    const rawLine = lines[i];
    const trimmed = rawLine.trim();

    if (!trimmed) {
      flushList();
      continue;
    }

    // Check for section header
    if (isSectionHeader(trimmed)) {
      flushList();
      const cleanHeader = trimmed.replace(/:$/, "").trim();
      const icon = getHeaderIcon(cleanHeader);
      output.push(
        `<h4 class="font-extrabold text-xs uppercase tracking-wider text-slate-800 dark:text-slate-100 flex items-center gap-1.5 mt-3.5 mb-1.5">
          <span class="text-amber-500 text-sm select-none">${icon}</span>
          <span class="font-bold">${escapeHtml(cleanHeader)}</span>
        </h4>`,
      );
      continue;
    }

    // Check for bullet point or tabbed line
    // e.g. starts with •, -, *, or starts with tab \t or indentation
    const isBullet =
      /^(\t|\s{2,})?[•\-\*]\s+/.test(rawLine) || /^(\t|\s{2,})/.test(rawLine);

    if (isBullet) {
      const cleanContent = trimmed.replace(/^[•\-\*]\s*/, "");

      // Check if it's a key-value pair like "Franchise: Pokemon"
      const colonIdx = cleanContent.indexOf(":");
      let formattedContent = escapeHtml(cleanContent);
      if (colonIdx > 0 && colonIdx < 30) {
        const key = cleanContent.slice(0, colonIdx);
        const val = cleanContent.slice(colonIdx + 1);
        formattedContent = `<span class="font-bold text-slate-700 dark:text-slate-200">${escapeHtml(key)}:</span><span class="text-slate-600 dark:text-slate-300 ml-1">${escapeHtml(val)}</span>`;
      }

      currentList.push(
        `<li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 ml-1 leading-relaxed">
          <span class="text-indigo-500 dark:text-indigo-400 font-bold shrink-0 select-none">•</span>
          <div>${formattedContent}</div>
        </li>`,
      );
      continue;
    }

    // Regular paragraph text
    flushList();
    output.push(
      `<p class="text-xs sm:text-[13px] text-slate-600 dark:text-slate-300 leading-relaxed mb-2">
        ${escapeHtml(trimmed)}
      </p>`,
    );
  }

  flushList();

  return output.join("\n");
}
