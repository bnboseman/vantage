function ReviewsApp() {
    const [all, setAll] = React.useState([]);
    const [filtered, setFiltered] = React.useState([]);
    const [loading, setLoading] = React.useState(false);
    const [error, setError] = React.useState("");

    const [q, setQ] = React.useState("");

    const PAGE_SIZE = 10;
    const [visibleCount, setVisibleCount] = React.useState(PAGE_SIZE);
    const [loadingMore, setLoadingMore] = React.useState(false);
    const sentinelRef = React.useRef(null);
    const observerRef = React.useRef(null);

    React.useEffect(() => {
        (async () => {
            setLoading(true);
            setError("");
            try {
                const res = await fetch("cochrane_reviews.json", { cache: "no-store" });
                if (!res.ok) throw new Error(`Failed to load JSON (${res.status})`);
                const data = await res.json();

                let list;
                if (Array.isArray(data)) {
                    const hasNested = data.some(x => Array.isArray(x));
                    list = hasNested ? data.flat() : data;
                } else if (data && Array.isArray(data.items)) {
                    list = data.items;
                } else {
                    list = [];
                }

                const normalized = list.map(x => ({
                    url: x.url ?? x[0] ?? "",
                    topic: x.topic ?? x[1] ?? "",
                    title: x.title ?? x[2] ?? "",
                    author: x.author ?? x[3] ?? "",
                    date: x.date ?? x[4] ?? ""
                }));

                setAll(normalized);
            } catch (e) {
                setError(e.message || String(e));
            } finally {
                setLoading(false);
            }
        })();
    }, []);

    React.useEffect(() => {
        const qLower = q.trim().toLowerCase();

        const next = qLower
            ? all.filter(row =>
                (row.title ?? "").toLowerCase().includes(qLower) ||
                (row.author ?? "").toLowerCase().includes(qLower) ||
                (row.topic ?? "").toLowerCase().includes(qLower)
            )
            : all;

        setFiltered(next);
        setVisibleCount(PAGE_SIZE);
    }, [all, q]);

    React.useEffect(() => {
        if (!sentinelRef.current) return;

        if (observerRef.current) observerRef.current.disconnect();

        const obs = new IntersectionObserver(
            entries => {
                const entry = entries[0];
                if (
                    entry.isIntersecting &&
                    !loading &&
                    !loadingMore &&
                    visibleCount < filtered.length
                ) {
                    setLoadingMore(true);
                    setTimeout(() => {
                        setVisibleCount(v => Math.min(v + PAGE_SIZE, filtered.length));
                        setLoadingMore(false);
                    }, 100);
                }
            },
            { root: null, rootMargin: "200px 0px", threshold: 0.01 }
        );

        obs.observe(sentinelRef.current);
        observerRef.current = obs;

        return () => obs.disconnect();
    }, [filtered.length, visibleCount, loading, loadingMore]);

    const rowsToRender = filtered.slice(0, visibleCount);

    return (
        <div className="content">
            <div className="toolbar">
                <input
                    value={q}
                    onChange={(e) => setQ(e.target.value)}
                    className="searchBar"
                />
                <span className="muted" style={{whiteSpace: "nowrap"}}>
        </span>
            </div>

            {error && <p className="error" style={{color: "red"}}>Error: {error}</p>}

            {!loading && rowsToRender.length > 0 && (
                <>
                    <table className="reviews">
                        <tbody>
                        {rowsToRender.map((r, i) => {
                            const key = (r.url || r.title) + ":" + i;
                            const authors = Array.isArray(r.author) ? r.author.join(", ") : r.author;

                            return (
                                <tr key={key} className="review">
                                    <td>
                                        <h2 className="title">
                                            {r.url ? (
                                                <a href={r.url} target="_blank" rel="noreferrer">
                                                    {r.title || "Untitled"}
                                                </a>
                                            ) : (
                                                r.title || "Untitled"
                                            )}
                                        </h2>
                                        {authors && <div className="authors">{authors}</div>}
                                        <div className="date">{r.date || "—"}</div>
                                    </td>
                                </tr>
                            );
                        })}
                        </tbody>
                    </table>

                    { }
                    <div ref={sentinelRef}/>

                    {loadingMore && <p className="muted">Loading more…</p>}
                    {visibleCount >= filtered.length && filtered.length > 0 && (
                        <p className="muted">End of results</p>
                    )}
                </>
            )}

            {!loading && !error && filtered.length === 0 && (
                <p className="muted">No results match your search.</p>
            )}
        </div>
    );
}

const root = ReactDOM.createRoot(document.getElementById("root"));
root.render(<ReviewsApp />);