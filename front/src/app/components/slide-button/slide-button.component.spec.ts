import { ComponentFixture, TestBed } from '@angular/core/testing';
import { SlideButtonComponent } from './slide-button.component';

describe('SlideButtonComponent', () => {
  let component: SlideButtonComponent;
  let fixture: ComponentFixture<SlideButtonComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [SlideButtonComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(SlideButtonComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have default values', () => {
    expect(component.label).toBe('Button');
    expect(component.duration).toBe(0.4);
    expect(component.angle).toBe(5.7);
    expect(component.backgroundColor).toBe('#fff');
    expect(component.textColor).toBe('#000');
    expect(component.negative).toBe(false);
    expect(component.type).toBe('button');
  });

  it('should emit buttonClick when clicked', () => {
    spyOn(component.buttonClick, 'emit');

    component.onClick();

    expect(component.buttonClick.emit).toHaveBeenCalled();
  });

  it('should return correct finalBackgroundColor when negative is false', () => {
    component.negative = false;
    component.backgroundColor = '#ff0000';

    expect(component.finalBackgroundColor).toBe('#ff0000');
  });

  it('should return correct finalBackgroundColor when negative is true', () => {
    component.negative = true;
    component.backgroundColor = '#ff0000';

    expect(component.finalBackgroundColor).toBe('#000');
  });

  it('should return correct finalButtonBackground when negative is false', () => {
    component.negative = false;

    expect(component.finalButtonBackground).toBe('transparent');
  });

  it('should return correct finalButtonBackground when negative is true', () => {
    component.negative = true;

    expect(component.finalButtonBackground).toBe('#fff');
  });

  it('should return correct finalBorderColor when negative is false', () => {
    component.negative = false;

    expect(component.finalBorderColor).toBe('#fff');
  });

  it('should return correct finalBorderColor when negative is true', () => {
    component.negative = true;

    expect(component.finalBorderColor).toBe('#000');
  });
});
