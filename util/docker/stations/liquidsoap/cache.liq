#<-----
AzuraCast Cache Functions
----->#

let settings.azuracast.cache = ()

let settings.azuracast.cache.enabled = settings.make(
    description="Enable the SQLite-based caching of computationally intensive data (i.e. AutoCue).",
    true
)

let azuracast.cache = ()

let azuracast.cache.db = ref(null())

def azuracast.cache.init() =
    if null.defined(azuracast.cache.db()) then
        null.get(azuracast.cache.db())
    else
        log(
            level=2,
            label="azuracast.cache",
            "Initializing cache database..."
        )

        db_path = file.temp(
            cleanup=true,
            'cache_',
            '.db'
        )

        log(
            level=3,
            label="azuracast.cache",
            "Cache path: #{db_path}"
        )

        db = sqlite(db_path)
        azuracast.cache.db := db

        db.table.create(
            "cache",
            preserve=true,
            [
                ("key", "STRING PRIMARY KEY"),
                ("value", "STRING")
            ]
        )

        db
    end
end

def azuracast.cache.get(key) =
    if not settings.azuracast.cache.enabled() then
        log(
            level=3,
            label="azuracast.cache",
            "Cache disabled!"
        )
        null()
    else
        db = azuracast.cache.init()

        results = db.select(
            table="cache",
            where="key=#{sqlite.escape(key)}"
        )

        if (list.length(results) == 0) then
            null()
        else
            let sqlite.row (
                r : { key: string, value: string }
            ) = list.hd(results)

            r.value
        end
    end
end

def azuracast.cache.has(key) =
    if not settings.azuracast.cache.enabled() then
        log(
            level=3,
            label="azuracast.cache",
            "Cache disabled!"
        )
        false
    else
        db = azuracast.cache.init()

        n = db.count(
            table="cache",
            where="key=#{sqlite.escape(key)}"
        )

        n > 0
    end
end

def azuracast.cache.unset(key) =
    if not settings.azuracast.cache.enabled() then
        log(
            level=3,
            label="azuracast.cache",
            "Cache disabled!"
        )
        null()
    else
        db = azuracast.cache.init()
        db.exec("DELETE FROM cache WHERE key=#{sqlite.escape(key)}")
    end
end

def azuracast.cache.set(key, value) =
    if not settings.azuracast.cache.enabled() then
        log(
            level=3,
            label="azuracast.cache",
            "Cache disabled!"
        )
        null()
    else
        _ = azuracast.cache.unset(key)

        db = azuracast.cache.init()
        db.insert(
            table="cache",
            {
                key=key,
                value=value
            }
        )
    end
end

def azuracast.cache.clear() =
    if not settings.azuracast.cache.enabled() then
        log(
            level=3,
            label="azuracast.cache",
            "Cache disabled!"
        )
        null()
    else
        db = azuracast.cache.init()
        db.exec("DELETE FROM cache")
    end
end
