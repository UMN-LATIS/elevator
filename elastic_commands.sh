
#curl -XDELETE "http://localhost:9200/elevator/"
#curl -X POST 'http://localhost:9200/elevator/_open'
# curl -XPUT http://localhost:9200/elevator/_settings -d '{
#   "index.mapping.total_fields.limit": 3000
# }'

curl -H 'Content-Type: application/json' -XPUT http://localhost:9200/elevator/ -d '{
  "settings": {
    "index.mapping.total_fields.limit": 10000,
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
          "filter": ["lowercase", "asciifolding"],
          "tokenizer": "standard"
        }
      }
    }
  }
}
'
curl  -H 'Content-Type: application/json' -XPUT http://localhost:9200/elevator/_mapping -d ' {
        "dynamic_templates": [
            {
            "text_fields": {
                "mapping": {
                    "fields": {
                        "raw": {
                            "ignore_above": 256,
                            "type": "keyword",
                            "normalizer": "lowerasciinormalizer"
                        }
                    },
                    "type": "text",
                    "copy_to": "my_all"
                },
                "match_mapping_type": "string",
                "match": "*"
            }
            },
            {
            "all_fields": {
                "match": "*",
                "mapping": {
                    "copy_to": "my_all"
                }
            }
            }
        ],

        "date_detection": false,
        "properties": {
            "my_all": {
                "type": "text"
            },
            "fileSearchData": {
                "type": "text"
            },
            "locationCache": {
                "type": "geo_point"
            },
            "lastModified": {
                "type": "date"
            },
            "title": {
                "fields": {
                    "raw": {
                        "ignore_above": 256,
                        "type": "keyword",
                        "normalizer": "lowerasciinormalizer"
                    }
                },
                "type": "text"
            }
        }
}'

curl -H 'Content-Type: application/json' -XPOST 'http://localhost:9200/_aliases' -d '{
  "actions": [
    {
      "add": {
        "index": "elevator",
        "alias": "sort_elevator"
      }
    }
  ]
}'






// chatgpt genreated

curl  -H 'Content-Type: application/json' -XPUT http://localhost:9200/elevator/_mapping -d '{
  "dynamic_templates": [
    {
      "text_fields": {
        "match_mapping_type": "string",
        "match": "*",
        "mapping": {
          "type": "text",
          "copy_to": "my_all",
          "fields": {
            "raw": {
              "type": "keyword",
              "ignore_above": 256,
              "normalizer": "lowerasciinormalizer"
            }
          }
        }
      }
    },
    {
      "all_fields": {
        "match": "*",
        "mapping": {
          "copy_to": "my_all"
        }
      }
    }
  ],
  
  "date_detection": false,
  
  "properties": {
    "my_all": {
      "type": "text"
    },
    "fileSearchData": {
      "type": "text"
    },
    "locationCache": {
      "type": "geo_point"
    },
    "lastModified": {
      "type": "date"
    },
    "title": {
      "type": "text",
      "fields": {
        "raw": {
          "type": "keyword",
          "ignore_above": 256,
          "normalizer": "lowerasciinormalizer"
        }
      }
    }
  },

  "settings": {
    "analysis": {
      "normalizer": {
        "lowerasciinormalizer": {
          "type": "custom",
          "char_filter": ["html_strip"],
          "tokenizer": "keyword",
          "filter": ["lowercase"]
        }
      }
    }
  }
}'



# mine from ES7
curl  -H 'Content-Type: application/json' -XPUT http://localhost:9200/elevator/_mapping -d' {
            "dynamic_templates": [
                {
                "text_fields": {
                    "mapping": {
                        "fields": {
                            "raw": {
                                "ignore_above": 256,
                                "type": "keyword",
                                "normalizer": "lowerasciinormalizer"
                            }
                        },
                        "type": "text",
                        "copy_to": "my_all"
                    },
                    "match_mapping_type": "string",
                    "match": "*"
                }
                },
                {
                "all_fields": {
                    "match": "*",
                    "mapping": {
                        "copy_to": "my_all"
                    }
                }
                }
            ],

            "date_detection": false,
            "properties": {
                "my_all": {
                    "type": "text"
                },
                "fileSearchData": {
                    "type": "text"
                },
                "locationCache": {
                    "type": "geo_point"
                },
                "lastModified": {
                    "type": "date"
                },
                "title": {
                    "fields": {
                        "raw": {
                            "ignore_above": 256,
                            "type": "keyword",
                            "normalizer": "lowerasciinormalizer"
                        }
                    },
                    "type": "text"
                }
            }
    }'