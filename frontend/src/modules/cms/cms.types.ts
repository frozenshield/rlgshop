export interface CMSBanner {
  id: string;
  title: string;
  subtitle: string;
  badgeText: string;
  ctaText: string;
  ctaLink: string;
  imageUrl: string;
  isActive: boolean;
}

export interface CMSStaticPages {
  about: string;
  terms: string;
  privacy: string;
  faq: string;
  [key: string]: string;
}

export interface ArticleSourceCitation {
  name: string;
  url?: string;
  note?: string;
}

export interface CMSBlogPost {
  id: string | number;
  slug?: string;
  title: string;
  category: string;
  author: string;
  date?: string;
  published_at?: string;
  status: string;
  summary: string;
  content?: string;
  image_url?: string;
  sources?: ArticleSourceCitation[];
  views_count?: number;
  is_featured?: boolean;
}

export interface CMSPayload {
  banners: CMSBanner[];
  pages: CMSStaticPages;
  blogs: CMSBlogPost[];
}
