function ReviewsApp() {
    const [all, setAll] = React.useState([]);
    const [filtered, setFiltered] = React.useState([]);
    const [loading, setLoading] = React.useState(false);
    const [error, setError] = React.useState("");

    const [q, setQ] = React.useState("");
    const [topics, setTopics] = React.useState([]);
    const [openSug, setOpenSug] = React.useState(false);
    const [hi, setHi] = React.useState(-1);
    const [selectedTopic, setSelectedTopic] = React.useState(null);

    const sugWrapRef = React.useRef(null);

    const PAGE_SIZE = 10;
    const [visibleCount, setVisibleCount] = React.useState(PAGE_SIZE);
    const [loadingMore, setLoadingMore] = React.useState(false);
    const sentinelRef = React.useRef(null);
    const observerRef = React.useRef(null);

    const normalize = (s) => (s || "").trim().replace(/\s+/g, " ");
    const toKey = (s) => normalize(s).toLowerCase();

    const findExactTopic = React.useCallback(
        (val) => {
            const key = toKey(val);
            return topics.find((t) => toKey(t) === key) || null;
        },
        [topics]
    );

    const selectTopic = React.useCallback((topic) => {
        setSelectedTopic(topic);
        setQ(topic);
        setOpenSug(false);
        setHi(-1);
    }, []);

    const suggs = React.useMemo(() => {
        const v = toKey(q);
        if (!v) return [];
        return topics.filter((t) => toKey(t).startsWith(v)).slice(0, 8);
    }, [topics, q]);

    React.useEffect(() => {
        (async () => {
            setLoading(true);
            setError("");
            try {
                const res = await fetch("js/cochrane_reviews.json", { cache: "no-store" });
                if (!res.ok) throw new Error(`Failed to load JSON (${res.status})`);
                const data = await res.json();

                let list;
                if (Array.isArray(data)) {
                    const hasNested = data.some((x) => Array.isArray(x));
                    list = hasNested ? data.flat() : data;
                } else if (data && Array.isArray(data.items)) {
                    list = data.items;
                } else {
                    list = [];
                }

                const normalized = list.map((x) => ({
                    url: x.url ?? x[0] ?? "",
                    topic: x.topic ?? x[1] ?? "",
                    title: x.title ?? x[2] ?? "",
                    author: x.author ?? x[3] ?? "",
                    date: x.date ?? x[4] ?? "",
                }));

                setAll(normalized);

                const uniqueTopics = Array.from(
                    new Set(normalized.map((r) => normalize(r.topic)).filter(Boolean))
                ).sort((a, b) => a.localeCompare(b));
                setTopics(uniqueTopics);
            } catch (e) {
                setError(e.message || String(e));
            } finally {
                setLoading(false);
            }
        })();
    }, []);

    React.useEffect(() => {
        if (selectedTopic) {
            const key = toKey(selectedTopic);
            const next = all.filter((row) => toKey(row.topic) === key);
            setFiltered(next);
        } else {
            setFiltered(all); // show ALL when nothing selected (empty or partial input)
        }
        setVisibleCount(PAGE_SIZE);
    }, [all, selectedTopic]);

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
                    // load immediately (no setTimeout needed)
                    setVisibleCount(v => Math.min(v + PAGE_SIZE, filtered.length));
                    setLoadingMore(false);
                }
            },
            { root: null, rootMargin: "600px 0px", threshold: 0 }
        );

        obs.observe(sentinelRef.current);
        observerRef.current = obs;
        return () => obs.disconnect();
    }, [filtered.length, visibleCount, loading, loadingMore]);

    const rowsToRender = filtered.slice(0, visibleCount);
    const hasSelected = !!selectedTopic;
    const matchCount = filtered.length;

    return (
        <div className="content">
            <div className="toolbar">
                <div className="suggestions" ref={sugWrapRef}>
                    <input
                        value={q}
                        onChange={(e) => {
                            setQ(e.target.value);
                            setOpenSug(true);
                            setHi(-1);
                            setSelectedTopic(null); // typing clears selection -> show all
                        }}
                        onFocus={() => setOpenSug(true)}
                        onBlur={() => setTimeout(() => setOpenSug(false), 120)}
                        onKeyDown={(e) => {
                            if (e.key === "Enter") {
                                const exact = findExactTopic(q);
                                if (exact) {
                                    e.preventDefault();
                                    selectTopic(exact); // filters to that topic
                                    return;
                                }
                            }
                            if (!openSug || suggs.length === 0) return;
                            if (e.key === "ArrowDown") {
                                e.preventDefault();
                                setHi((i) => (i + 1) % suggs.length);
                            } else if (e.key === "ArrowUp") {
                                e.preventDefault();
                                setHi((i) => (i <= 0 ? suggs.length - 1 : i - 1));
                            } else if (e.key === "Enter") {
                                if (hi >= 0) {
                                    e.preventDefault();
                                    selectTopic(suggs[hi]);
                                }
                            } else if (e.key === "Escape") {
                                setOpenSug(false);
                                setHi(-1);
                            }
                        }}
                        className="searchBar"
                        placeholder="Search by topic…"
                        aria-label="Search by topic"
                    />

                    {openSug && suggs.length > 0 && (
                        <ul className="suggestionsList" role="listbox">
                            {suggs.map((t, idx) => (
                                <li
                                    key={t}
                                    role="option"
                                    aria-selected={idx === hi}
                                    className={idx === hi ? "suggestion is-active" : "suggestion"}
                                    onMouseDown={(e) => e.preventDefault()}
                                    onClick={() => selectTopic(t)}
                                    onMouseEnter={() => setHi(idx)}
                                >
                                    {t}
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <span className="muted" style={{ whiteSpace: "nowrap" }}>
          {loading ? "Loading…" : ""}
        </span>
            </div>

            {hasSelected && (
                <div style={{margin: "16px 0 8px 0"}}>
  <span className="selectedTopics">
    Topics:{" "}
      <button
          onClick={() => {
              setSelectedTopic(null);
              setQ("");
          }}
          className = "dismissTopic"
      >
      {selectedTopic} &times;
    </button>
  </span>

                    <div style={{fontWeight: 600, marginTop: "6px"}}>
                        <strong>{matchCount}</strong> Cochrane Reviews Matching{" "}
                        <strong>{selectedTopic} in Cochrane Topic</strong>
                    </div>
                </div>
            )}

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

                    <div ref={sentinelRef} />

                    {loadingMore && <p className="muted">Loading more…</p>}
                    {visibleCount >= filtered.length && filtered.length > 0 && (
                        <p className="muted">End of results</p>
                    )}
                </>
            )}

            {!loading && !error && rowsToRender.length === 0 && hasSelected && (
                <p className="muted">No reviews match this topic.</p>
            )}
        </div>
    );
}

const root = ReactDOM.createRoot(document.getElementById("root"));
root.render(<ReviewsApp />);