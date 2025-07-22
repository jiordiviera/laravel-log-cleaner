import { useState, useEffect } from 'react'
import { Button } from '@/components/ui/button'
import { ExternalLink, Github as GitHubIcon, Copy, Check } from 'lucide-react'
import ReactMarkdown from 'react-markdown'
import toast, { Toaster } from 'react-hot-toast'

interface Release {
  id: number
  tag_name: string
  name: string
  body: string
  published_at: string
  html_url: string
  prerelease: boolean
}

interface ReadmeData {
  content: string
  encoding: string
}

function App() {
  const [releases, setReleases] = useState<Release[]>([])
  const [readme, setReadme] = useState<string>('')
  const [loading, setLoading] = useState(false)
  const [readmeLoading, setReadmeLoading] = useState(false)
  const [showAll, setShowAll] = useState(false)
  const [copiedStates, setCopiedStates] = useState<{[key: string]: boolean}>({})

  const loadReleases = async () => {
    setLoading(true)
    try {
      const response = await fetch('https://api.github.com/repos/jiordiviera/laravel-log-cleaner/releases')
      const data = await response.json()
      setReleases(data)
    } catch (error) {
      console.error('Error loading releases:', error)
    } finally {
      setLoading(false)
    }
  }

  const copyToClipboard = async (text: string, id: string) => {
    try {
      await navigator.clipboard.writeText(text)
      setCopiedStates(prev => ({ ...prev, [id]: true }))
      toast.success('📋 Copied to clipboard!')
      
      setTimeout(() => {
        setCopiedStates(prev => ({ ...prev, [id]: false }))
      }, 2000)
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    } catch (error) {
      toast.error('❌ Failed to copy to clipboard')
    }
  }

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  }

  const loadReadme = async () => {
    setReadmeLoading(true)
    try {
      const response = await fetch('https://api.github.com/repos/jiordiviera/laravel-log-cleaner/readme')
      const data: ReadmeData = await response.json()
      const decodedContent = atob(data.content)
      setReadme(decodedContent)
    } catch (error) {
      console.error('Error loading README:', error)
      toast.error('❌ Failed to load README')
    } finally {
      setReadmeLoading(false)
    }
  }

  useEffect(() => {
    loadReleases()
    loadReadme()
  }, [])

  return (
    <div className="min-h-screen bg-background">
      <Toaster position="top-right" />
      
      {/* Header */}
      <header className="bg-card border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center py-6">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <div className="w-8 h-8 bg-primary rounded"></div>
              </div>
              <div className="ml-4">
                <h1 className="text-2xl font-bold text-foreground">Laravel Log Cleaner</h1>
                <p className="text-sm text-muted-foreground">Professional log management for Laravel applications</p>
              </div>
            </div>
            <div className="flex items-center space-x-4">
              <Button variant="outline" asChild>
                <a href="https://github.com/jiordiviera/laravel-log-cleaner" target="_blank" rel="noopener noreferrer" className="flex items-center">
                  <GitHubIcon className="w-4 h-4 mr-2" />
                  View on GitHub
                </a>
              </Button>
            </div>
          </div>
        </div>
      </header>

      {/* Quick Start Section */}
      <section className="bg-card">
        <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
          <div className="max-w-3xl">
            <h2 className="text-3xl font-bold text-foreground mb-4">Quick Start</h2>
            <p className="text-lg text-muted-foreground mb-8">
              Get started with Laravel Log Cleaner in seconds. Install the package and start managing your logs efficiently.
            </p>
            
            <div className="bg-secondary/30 rounded-lg p-6">
              <div className="flex items-center justify-between mb-3">
                <h3 className="text-sm font-medium text-foreground">Installation</h3>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => copyToClipboard('composer require jiordiviera/laravel-log-cleaner', 'install')}
                  className="h-8"
                >
                  {copiedStates.install ? (
                    <Check className="w-4 h-4 text-primary" />
                  ) : (
                    <Copy className="w-4 h-4" />
                  )}
                </Button>
              </div>
              <pre className="bg-muted p-4 rounded text-sm overflow-x-auto">
                <code className="text-foreground">composer require jiordiviera/laravel-log-cleaner</code>
              </pre>
            </div>
            
            <div className="mt-6 bg-secondary/30 rounded-lg p-6">
              <div className="flex items-center justify-between mb-3">
                <h3 className="text-sm font-medium text-foreground">Basic Usage</h3>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => copyToClipboard('php artisan log:clear', 'basic')}
                  className="h-8"
                >
                  {copiedStates.basic ? (
                    <Check className="w-4 h-4 text-primary" />
                  ) : (
                    <Copy className="w-4 h-4" />
                  )}
                </Button>
              </div>
              <pre className="bg-muted p-4 rounded text-sm overflow-x-auto">
                <code className="text-foreground">php artisan log:clear</code>
              </pre>
            </div>
          </div>
        </div>
      </section>

      {/* README Section */}
      <section className="bg-background">
        <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
          <div className="max-w-4xl">
            <h2 className="text-3xl font-bold text-foreground mb-8">Documentation</h2>
            
            {readmeLoading ? (
              <div className="flex items-center justify-center py-12">
                <div className="w-8 h-8 border-4 border-muted border-t-primary rounded-full animate-spin"></div>
                <span className="ml-3 text-muted-foreground">Loading documentation...</span>
              </div>
            ) : readme ? (
              <div className="bg-card rounded-lg shadow-sm border p-8">
                <div className="prose prose-neutral max-w-none">
                  <ReactMarkdown
                  
                    components={{
                      pre: ({ children }) => (
                        <pre className="bg-muted p-4 rounded-lg overflow-x-auto text-sm border">
                          {children}
                        </pre>
                      ),
                      code: ({ children, className }) => {
                        const isInline = !className
                        return isInline ? (
                          <code className="bg-secondary/50 text-foreground px-1.5 py-0.5 rounded text-sm font-mono">
                            {children}
                          </code>
                        ) : (
                          <code className="text-foreground">{children}</code>
                        )
                      },
                      h1: ({ children }) => (
                        <h1 className="text-3xl font-bold text-foreground mb-6 pb-3 border-b">
                          {children}
                        </h1>
                      ),
                      h2: ({ children }) => (
                        <h2 className="text-2xl font-bold text-foreground mb-4 mt-8">
                          {children}
                        </h2>
                      ),
                      h3: ({ children }) => (
                        <h3 className="text-xl font-semibold text-foreground mb-3 mt-6">
                          {children}
                        </h3>
                      ),
                      h4: ({ children }) => (
                        <h4 className="text-lg font-semibold text-foreground mb-2 mt-4">
                          {children}
                        </h4>
                      ),
                      h5: ({ children }) => (
                        <h5 className="text-base font-semibold text-foreground mb-2 mt-3">
                          {children}
                        </h5>
                      ),
                      h6: ({ children }) => (
                        <h6 className="text-sm font-semibold text-foreground mb-2 mt-3">
                          {children}
                        </h6>
                      ),
                      p: ({ children }) => (
                        <p className="text-muted-foreground mb-4 leading-relaxed">
                          {children}
                        </p>
                      ),
                      ul: ({ children }) => (
                        <ul className="list-disc pl-6 mb-4 space-y-1 text-muted-foreground">
                          {children}
                        </ul>
                      ),
                      ol: ({ children }) => (
                        <ol className="list-decimal pl-6 mb-4 space-y-1 text-muted-foreground">
                          {children}
                        </ol>
                      ),
                      li: ({ children }) => (
                        <li className="text-muted-foreground">
                          {children}
                        </li>
                      ),
                      blockquote: ({ children }) => (
                        <blockquote className="border-l-4 border-primary/30 pl-4 py-2 mb-4 text-muted-foreground italic bg-secondary/20 rounded-r">
                          {children}
                        </blockquote>
                      ),
                      table: ({ children }) => (
                        <div className="overflow-x-auto mb-4 border rounded-lg">
                          <table className="w-full border-collapse">
                            {children}
                          </table>
                        </div>
                      ),
                      thead: ({ children }) => (
                        <thead className="bg-secondary/50">
                          {children}
                        </thead>
                      ),
                      tbody: ({ children }) => (
                        <tbody className="divide-y">
                          {children}
                        </tbody>
                      ),
                      th: ({ children }) => (
                        <th className="px-4 py-2 text-left font-semibold text-foreground border-r last:border-r-0">
                          {children}
                        </th>
                      ),
                      td: ({ children }) => (
                        <td className="px-4 py-2 text-muted-foreground border-r last:border-r-0">
                          {children}
                        </td>
                      ),
                      tr: ({ children }) => (
                        <tr className="hover:bg-secondary/20">
                          {children}
                        </tr>
                      ),
                      a: ({ href, children }) => (
                        <a href={href} className="text-primary hover:underline" target="_blank" rel="noopener noreferrer">
                          {children}
                        </a>
                      ),
                      strong: ({ children }) => (
                        <strong className="font-semibold text-foreground">
                          {children}
                        </strong>
                      ),
                      em: ({ children }) => (
                        <em className="italic text-muted-foreground">
                          {children}
                        </em>
                      )
                    }}
                  >
                    {readme}
                  </ReactMarkdown>
                </div>
              </div>
            ) : (
              <div className="text-center py-12">
                <p className="text-muted-foreground">Failed to load documentation</p>
                <Button variant="outline" onClick={loadReadme} className="mt-4">
                  Retry
                </Button>
              </div>
            )}
          </div>
        </div>
      </section>


      {/* Releases */}
      <section className="bg-card">
        <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
          <div className="max-w-4xl">
            <h2 className="text-3xl font-bold text-foreground mb-8">Latest Releases</h2>
            
            {loading && (
              <div className="flex items-center justify-center py-12">
                <div className="w-8 h-8 border-4 border-muted border-t-primary rounded-full animate-spin"></div>
                <span className="ml-3 text-muted-foreground">Loading releases...</span>
              </div>
            )}

            {!loading && releases.length > 0 && (
              <div className="space-y-4">
                {releases.slice(0, showAll ? releases.length : 3).map((release) => (
                  <div key={release.id} className="bg-secondary/30 rounded-lg p-6 border">
                    <div className="flex items-center justify-between mb-3">
                      <div className="flex items-center space-x-3">
                        <span className={`px-2 py-1 rounded text-sm font-medium ${ 
                          release.prerelease ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                        }`}>
                          {release.tag_name}
                        </span>
                        <span className="text-sm text-muted-foreground">
                          {formatDate(release.published_at)}
                        </span>
                        {release.prerelease && (
                          <span className="px-2 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300 text-xs rounded">
                            Pre-release
                          </span>
                        )}
                      </div>
                      <Button variant="ghost" size="sm" asChild>
                        <a href={release.html_url} target="_blank" rel="noopener noreferrer" className="flex items-center">
                          <ExternalLink className="w-3 h-3 mr-1" />
                          GitHub
                        </a>
                      </Button>
                    </div>
                    <h3 className="text-lg font-semibold text-foreground mb-2">
                      {release.name || release.tag_name}
                    </h3>
                    <div className="text-muted-foreground text-sm">
                      {release.body ? (
                        <p>{release.body.substring(0, 150)}...</p>
                      ) : (
                        <p>No description available.</p>
                      )}
                    </div>
                  </div>
                ))}

                {releases.length > 3 && (
                  <div className="text-center pt-4">
                    <Button variant="outline" onClick={() => setShowAll(!showAll)}>
                      {showAll ? 'Show Less' : `Show All ${releases.length} Releases`}
                    </Button>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-primary text-primary-foreground">
        <div className="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <p className="text-sm opacity-90">
                &copy; 2025 Laravel Log Cleaner. MIT License.
              </p>
            </div>
            <div className="flex items-center space-x-6">
              <a href="https://github.com/jiordiviera" target="_blank" rel="noopener noreferrer" 
                 className="opacity-80 hover:opacity-100 transition-opacity text-sm">
                GitHub
              </a>
              <a href="https://twitter.com/jiordiviera" target="_blank" rel="noopener noreferrer" 
                 className="opacity-80 hover:opacity-100 transition-opacity text-sm">
                Twitter
              </a>
            </div>
          </div>
        </div>
      </footer>
    </div>
  )
}

export default App