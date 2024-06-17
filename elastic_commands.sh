
#curl -XDELETE "http://localhost:9200/elevator/"
#curl -X POST 'http://localhost:9200/elevator/_open'
# curl -XPUT http://localhost:9200/elevator/_settings -d '{
#   "index.mapping.total_fields.limit": 3000
# }'

curl -XPUT http://localhost:9200/elevator/ -d '{
  "settings": {
    "index.mapping.total_fields.limit": 5000,
    "analysis": {
      "normalizer": {
        "lowerasciinormalizer": {
          "filter": ["lowercase", "asciifolding"],
          "type": "custom"
        }
      },
      "analyzer": {
        "case_insensitive_sort": {
          "filter": ["lowercase", "asciifolding"],
          "tokenizer": "keyword"
        },
        "default": {
          "filter": ["standard", "lowercase", "asciifolding"],
          "tokenizer": "standard"
        }
      }
    }
  }
}
'
curl -XPUT http://localhost:9200/elevator/asset/_mapping -d '{
  "asset": {
    "_all": {
      "enabled": true
    },
    "dynamic_templates": [
      {
        "text_fields": {
          "match": "*",
          "match_mapping_type": "string",
          "mapping": {
            "fields": {
              "raw": {
                "ignore_above": 256,
                "type": "keyword",
                "normalizer": "lowerasciinormalizer"
              }
            },
            "index": "analyzed",
            "omit_norms": false,
            "type": "text"
          }
        }
      }
    ],
    "date_detection": false,
    "properties": {
      "fileSearchData": {
        "boost": 0.8,
        "type": "text"
      },
      "locationCache": {
        "type": "geo_point"
      },
      "lastModified": {
        "type": "date"
      },
      "title" : {
        "type" : "text",
        "fields" : {
          "raw" : {
            "type" : "keyword",
            "ignore_above" : 256,
            "normalizer" : "lowerasciinormalizer"
          }
        }
      }
    }
  }
}
'
