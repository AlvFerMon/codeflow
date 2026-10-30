package main

import "testing"

func TestSafeRel(t *testing.T) {
	ok := []string{"README.md", "src/a/b.go", ".github/workflows/ci.yml", "a..b/c"}
	bad := []string{"", "../x", "a/../../x", "/etc/passwd", "C:/Windows/x", "c:x", ".codeflow/state.json", ".CodeFlow/x", "a\\..\\..\\x", "a\x00b"}
	for _, p := range ok {
		if err := safeRel(p); err != nil {
			t.Errorf("%q debería valer: %v", p, err)
		}
	}
	for _, p := range bad {
		if safeRel(p) == nil {
			t.Errorf("%q debería rechazarse", p)
		}
	}
}
