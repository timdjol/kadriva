const TMDB = 'https://image.tmdb.org/t/p/';

export function imageUrl(path, size = 'w500') {
    if (!path) return '';
    if (/^https?:\/\//i.test(path)) return path;
    return `${TMDB}${size}${path.startsWith('/') ? path : `/${path}`}`;
}

export function posterOf(title) {
    return imageUrl(title?.poster_url || title?.poster_path || title?.poster);
}

export function backdropOf(title) {
    return imageUrl(title?.backdrop_url || title?.backdrop_path || title?.backdrop, 'original');
}

export function yearOf(title) {
    const value = title?.year || title?.release_year || title?.release_date || title?.first_air_date;
    return value ? String(value).slice(0, 4) : '—';
}

export function ratingOf(title) {
    const value = Number(title?.rating ?? title?.vote_average);
    return Number.isFinite(value) && value > 0 ? value.toFixed(1) : '—';
}

export function titleName(title) {
    return title?.name || title?.title || title?.localized_name || 'Без названия';
}

export function titlePath(title) {
    const section = title?.type === 'series' ? 'series' : 'movies';
    return `/${section}/${title?.slug || 'title'}-${title?.id}`;
}

export function durationLabel(seconds) {
    if (!seconds) return '';
    const minutes = seconds > 400 ? Math.round(seconds / 60) : seconds;
    return `${minutes} мин`;
}
