#<-----
AzuraCast Cache Functions
----->#

let azuracast.cache = ()

let azuracast.cache.db = ref(null())

def azuracast.cache.init() =
    if null.defined(azuracast.cache.db()) then
        let db = null.get(azuracast.cache.db())
        db
    else
        log(
            level=2,
            label="azuracast.cache",
            "Initializing cache database..."
        )

        let db_path = file.temp(
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
    let db = azuracast.cache.init()

    let results = db.select(
        table="cache",
        where="key=#{sqlite.escape(key)}"
    )

    let sqlite.row (
        r : { key: string, value: string }
    ) = list.hd(results)

    r.value
end

def azuracast.cache.has(key) =
    let db = azuracast.cache.init()

    n = db.count(
        table="cache",
        where="key=#{sqlite.escape(key)}"
    )

    n > 0
end

def azuracast.cache.set(key, value) =
    let db = azuracast.cache.init()

    db.insert(
        table="cache",
        {
            key=key,
            value=value
        }
    )
end
