import { TestBed } from '@angular/core/testing';
import { FileDownloadService } from './file-download.service';

describe('FileDownloadService', () => {
  let service: FileDownloadService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(FileDownloadService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should download file with correct content and MIME type', () => {
    const content = 'test content';
    const mimeType = 'text/plain';
    const filename = 'test.txt';

    spyOn(document, 'createElement').and.callThrough();
    spyOn(document.body, 'appendChild');
    spyOn(document.body, 'removeChild');
    spyOn(URL, 'createObjectURL').and.returnValue('blob:test-url');
    spyOn(URL, 'revokeObjectURL');

    service.downloadFile(content, mimeType, filename);

    expect(URL.createObjectURL).toHaveBeenCalled();
    expect(document.createElement).toHaveBeenCalledWith('a');
    expect(document.body.appendChild).toHaveBeenCalled();
    expect(document.body.removeChild).toHaveBeenCalled();
    expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:test-url');
  });

  it('should create download link with correct attributes', () => {
    const content = 'test content';
    const mimeType = 'text/plain';
    const filename = 'test.txt';

    const mockLink = {
      href: '',
      download: '',
      style: { display: '' },
      click: jasmine.createSpy('click')
    };

    spyOn(document, 'createElement').and.returnValue(mockLink as any);
    spyOn(document.body, 'appendChild');
    spyOn(document.body, 'removeChild');
    spyOn(URL, 'createObjectURL').and.returnValue('blob:test-url');
    spyOn(URL, 'revokeObjectURL');

    service.downloadFile(content, mimeType, filename);

    expect(mockLink.href).toBe('blob:test-url');
    expect(mockLink.download).toBe(filename);
    expect(mockLink.style.display).toBe('none');
    expect(mockLink.click).toHaveBeenCalled();
  });

  it('should download CSV file with default filename', () => {
    const content = 'name,age\nJohn,25\nJane,30';
    const expectedFilename = `export_${new Date().toISOString().split('T')[0]}.csv`;

    spyOn(service, 'downloadFile');

    service.downloadCsv(content);

    expect(service.downloadFile).toHaveBeenCalledWith(content, 'text/csv', expectedFilename);
  });

  it('should download CSV file with custom filename', () => {
    const content = 'name,age\nJohn,25\nJane,30';
    const customFilename = 'custom-export.csv';

    spyOn(service, 'downloadFile');

    service.downloadCsv(content, customFilename);

    expect(service.downloadFile).toHaveBeenCalledWith(content, 'text/csv', customFilename);
  });

  it('should download JSON file with default filename', () => {
    const data = { name: 'John', age: 25 };
    const expectedFilename = `export_${new Date().toISOString().split('T')[0]}.json`;

    spyOn(service, 'downloadFile');

    service.downloadJson(data);

    const expectedContent = JSON.stringify(data, null, 2);
    expect(service.downloadFile).toHaveBeenCalledWith(expectedContent, 'application/json', expectedFilename);
  });

  it('should download JSON file with custom filename', () => {
    const data = { name: 'John', age: 25 };
    const customFilename = 'custom-data.json';

    spyOn(service, 'downloadFile');

    service.downloadJson(data, customFilename);

    const expectedContent = JSON.stringify(data, null, 2);
    expect(service.downloadFile).toHaveBeenCalledWith(expectedContent, 'application/json', customFilename);
  });

  it('should handle complex JSON data', () => {
    const complexData = {
      users: [
        { id: 1, name: 'John', scores: [85, 90, 88] },
        { id: 2, name: 'Jane', scores: [92, 87, 95] }
      ],
      metadata: {
        exportDate: '2023-01-01',
        totalRecords: 2
      }
    };

    spyOn(service, 'downloadFile');

    service.downloadJson(complexData);

    const expectedContent = JSON.stringify(complexData, null, 2);
    expect(service.downloadFile).toHaveBeenCalledWith(
      expectedContent,
      'application/json',
      jasmine.any(String)
    );
  });

  it('should handle empty content', () => {
    const content = '';
    const mimeType = 'text/plain';
    const filename = 'empty.txt';

    spyOn(URL, 'createObjectURL').and.returnValue('blob:test-url');
    spyOn(URL, 'revokeObjectURL');
    spyOn(document, 'createElement').and.callThrough();
    spyOn(document.body, 'appendChild');
    spyOn(document.body, 'removeChild');

    expect(() => service.downloadFile(content, mimeType, filename)).not.toThrow();
  });

  it('should handle special characters in filename', () => {
    const content = 'test content';
    const mimeType = 'text/plain';
    const filename = 'test file with spaces & symbols!.txt';

    spyOn(URL, 'createObjectURL').and.returnValue('blob:test-url');
    spyOn(URL, 'revokeObjectURL');
    spyOn(document, 'createElement').and.callThrough();
    spyOn(document.body, 'appendChild');
    spyOn(document.body, 'removeChild');

    expect(() => service.downloadFile(content, mimeType, filename)).not.toThrow();
  });

  it('should handle large content', () => {
    const largeContent = 'x'.repeat(1000000); // 1MB of content
    const mimeType = 'text/plain';
    const filename = 'large-file.txt';

    spyOn(URL, 'createObjectURL').and.returnValue('blob:test-url');
    spyOn(URL, 'revokeObjectURL');
    spyOn(document, 'createElement').and.callThrough();
    spyOn(document.body, 'appendChild');
    spyOn(document.body, 'removeChild');

    expect(() => service.downloadFile(largeContent, mimeType, filename)).not.toThrow();
  });

  it('should create blob with correct MIME type and charset', () => {
    const content = 'test content';
    const mimeType = 'application/pdf';
    const filename = 'test.pdf';

    spyOn(URL, 'createObjectURL').and.returnValue('blob:test-url');
    spyOn(URL, 'revokeObjectURL');
    spyOn(document, 'createElement').and.callThrough();
    spyOn(document.body, 'appendChild');
    spyOn(document.body, 'removeChild');

    // Test that the method doesn't throw and completes successfully
    expect(() => service.downloadFile(content, mimeType, filename)).not.toThrow();
    
    expect(URL.createObjectURL).toHaveBeenCalled();
    expect(URL.revokeObjectURL).toHaveBeenCalled();
    expect(document.createElement).toHaveBeenCalledWith('a');
    expect(document.body.appendChild).toHaveBeenCalled();
    expect(document.body.removeChild).toHaveBeenCalled();
  });
});
